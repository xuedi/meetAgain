<?php declare(strict_types=1);

namespace Module\Ballot\Internal;

use App\Entity\User;
use DateTimeImmutable;
use DomainException;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Module\Ballot\Contract\BallotInterface;
use Module\Ballot\Contract\BallotOutcome;
use Module\Ballot\Contract\BallotRequest;
use Module\Ballot\Contract\BallotSubject;
use Module\Ballot\Contract\BallotView;
use Module\Ballot\Contract\Candidate;
use Module\Ballot\Internal\Entity\Ballot;
use Module\Ballot\Internal\Entity\BallotOption;
use Module\Ballot\Internal\Entity\BallotVote;
use Module\Ballot\Internal\Repository\BallotRepository;
use Module\Ballot\Internal\Repository\BallotVoteRepository;
use Override;

final readonly class BallotService implements BallotInterface
{
    private const int MINIMUM_CANDIDATES = 2;

    public function __construct(
        private BallotRepository $ballots,
        private BallotVoteRepository $votes,
        private EntityManagerInterface $entityManager,
        private TallyCalculator $calculator,
        private ElectorateRegistry $electorate,
        private VisibilityFilterService $visibility,
        private SettlementRegistry $settlement,
    ) {}

    #[Override]
    public function open(BallotRequest $request): int
    {
        $keys = $request->candidateKeys();
        if (count($keys) < self::MINIMUM_CANDIDATES) {
            throw new InvalidArgumentException('A ballot needs at least two distinct candidates.');
        }

        $ballot = new Ballot(
            $request->purpose,
            $request->deadline,
            $request->openedByUserId,
            $request->tallyMode,
            $request->settlementMode,
            $this->now(),
            $request->subject?->type,
            $request->subject?->id,
        );

        $position = 0;
        $seen = [];
        foreach ($request->candidates as $candidate) {
            if (isset($seen[$candidate->key])) {
                continue;
            }
            $seen[$candidate->key] = true;
            $ballot->addOption(new BallotOption($ballot, $candidate->key, $candidate->label, $position++));
        }

        $this->entityManager->persist($ballot);
        $this->entityManager->flush();

        return (int) $ballot->getId();
    }

    #[Override]
    public function cast(int $ballotId, int $userId, array $candidateKeys): void
    {
        $ballot = $this->mustFind($ballotId);
        if (!$this->isVotable($ballot)) {
            throw new DomainException('This ballot no longer accepts votes.');
        }
        if (!$this->isVisible($ballot, $userId) || !$this->isElector($ballot, $userId)) {
            throw new DomainException('This member may not vote on this ballot.');
        }

        $this->withdrawVotes($ballot, $userId);
        $this->recordVotes($ballot, $userId, $this->selectionFor($ballot, $candidateKeys));
    }

    private function withdrawVotes(Ballot $ballot, int $userId): void
    {
        foreach ($this->votes->findForVoter($ballot, $userId) as $previous) {
            $this->entityManager->remove($previous);
        }

        $this->entityManager->flush();
    }

    /**
     * @param list<string> $selection
     */
    private function recordVotes(Ballot $ballot, int $userId, array $selection): void
    {
        $now = $this->now();
        foreach ($selection as $key) {
            $this->entityManager->persist(new BallotVote($ballot, $this->userReference($userId), $key, $now));
        }

        $this->entityManager->flush();
    }

    #[Override]
    public function tally(int $ballotId): BallotOutcome
    {
        $ballot = $this->mustFind($ballotId);
        if ($ballot->getStatus()->isResolved()) {
            throw new DomainException('This ballot is already resolved.');
        }

        $this->applyTally($ballot);
        $this->entityManager->flush();

        return $this->outcome($ballot);
    }

    #[Override]
    public function settle(int $ballotId, string $winningKey, ?int $settledByUserId = null): BallotOutcome
    {
        $ballot = $this->mustFind($ballotId);
        if ($ballot->getStatus()->isResolved()) {
            throw new DomainException('This ballot is already resolved.');
        }
        if (!in_array($winningKey, $ballot->getOptionKeys(), true)) {
            throw new InvalidArgumentException('That candidate is not on this ballot.');
        }

        if ($ballot->getStatus()->acceptsVotes()) {
            $this->applyTally($ballot);
        }
        $ballot->recordSettlement($winningKey, $settledByUserId, $this->now());
        $this->entityManager->flush();

        $outcome = $this->outcome($ballot);
        $this->settlement->settled($outcome);

        return $outcome;
    }

    #[Override]
    public function abandon(int $ballotId, ?int $abandonedByUserId = null): void
    {
        $ballot = $this->mustFind($ballotId);
        if ($ballot->getStatus()->isResolved()) {
            return;
        }

        $ballot->abandon($abandonedByUserId, $this->now());
        $this->entityManager->flush();
    }

    #[Override]
    public function view(int $ballotId, ?int $viewerUserId): ?BallotView
    {
        $ballot = $this->ballots->find($ballotId);
        if (!$ballot instanceof Ballot || !$this->isVisible($ballot, $viewerUserId)) {
            return null;
        }

        return $this->toView($ballot, $viewerUserId);
    }

    #[Override]
    public function listOpenFor(int $viewerUserId): array
    {
        $views = [];
        foreach ($this->openTo($viewerUserId) as $ballot) {
            $views[] = $this->toView($ballot, $viewerUserId);
        }

        return $views;
    }

    #[Override]
    public function listForSubject(BallotSubject $subject, ?int $viewerUserId): array
    {
        $views = [];
        foreach ($this->visibleOnly($this->ballots->findForSubject($subject->type, $subject->id), $viewerUserId) as $ballot) {
            $views[] = $this->toView($ballot, $viewerUserId);
        }

        return $views;
    }

    #[Override]
    public function mayVote(int $ballotId, int $userId): bool
    {
        $ballot = $this->ballots->find($ballotId);

        return $ballot instanceof Ballot
            && $this->isVotable($ballot)
            && $this->isVisible($ballot, $userId)
            && $this->isElector($ballot, $userId);
    }

    #[Override]
    public function countOpenFor(int $viewerUserId): int
    {
        return count($this->openTo($viewerUserId));
    }

    private function applyTally(Ballot $ballot): void
    {
        $result = $this->calculator->decide($ballot->getOptionKeys(), $this->votes->countByOptionKey((int) $ballot->getId()));
        $ballot->recordTally($result->winningKey, $result->tiedKeys);
    }

    /**
     * @param  list<string> $candidateKeys
     * @return list<string>
     */
    private function selectionFor(Ballot $ballot, array $candidateKeys): array
    {
        $selection = array_values(array_intersect(array_unique($candidateKeys), $ballot->getOptionKeys()));
        $maximum = $ballot->getTallyMode()->maximumSelections();

        return $maximum === null ? $selection : array_slice($selection, 0, $maximum);
    }

    private function toView(Ballot $ballot, ?int $viewerUserId): BallotView
    {
        $ballotId = (int) $ballot->getId();
        $candidates = [];
        foreach ($ballot->getOptions() as $option) {
            $candidates[] = new Candidate($option->getOptionKey(), $option->getLabel());
        }

        $counts = $this->votes->countByOptionKey($ballotId);
        $tally = [];
        foreach ($ballot->getOptionKeys() as $key) {
            $tally[$key] = $counts[$key] ?? 0;
        }

        return new BallotView(
            $ballotId,
            $ballot->getPurpose(),
            $ballot->getStatus(),
            $candidates,
            $tally,
            $ballot->getDeadline(),
            $ballot->getTallyMode(),
            $ballot->getSettlementMode(),
            $ballot->getOpenedByUserId(),
            $this->votes->countVoters($ballotId),
            $this->subjectOf($ballot),
            $ballot->getWinningKey(),
            $ballot->getTiedKeys(),
            $viewerUserId === null ? [] : $this->votes->findSelection($ballotId, $viewerUserId),
            $viewerUserId !== null && $this->isVotable($ballot) && $this->isElector($ballot, $viewerUserId),
        );
    }

    private function outcome(Ballot $ballot): BallotOutcome
    {
        return new BallotOutcome(
            (int) $ballot->getId(),
            $ballot->getPurpose(),
            $ballot->getStatus(),
            $ballot->getWinningKey(),
            $ballot->getTiedKeys(),
            $this->subjectOf($ballot),
            $ballot->getSettledByUserId(),
        );
    }

    private function subjectOf(Ballot $ballot): ?BallotSubject
    {
        $type = $ballot->getSubjectType();
        $id = $ballot->getSubjectId();

        return $type === null || $id === null ? null : new BallotSubject($type, $id);
    }

    private function isVotable(Ballot $ballot): bool
    {
        return $ballot->getStatus()->acceptsVotes() && $ballot->getDeadline() > $this->now();
    }

    private function isElector(Ballot $ballot, int $userId): bool
    {
        return $this->electorate->mayVote($ballot->getPurpose(), $userId, $this->subjectOf($ballot));
    }

    private function isVisible(Ballot $ballot, ?int $viewerUserId): bool
    {
        return $this->visibility->allows($ballot->getPurpose(), (int) $ballot->getId(), $viewerUserId);
    }

    /**
     * @return list<Ballot>
     */
    private function openTo(int $viewerUserId): array
    {
        $electable = [];
        foreach ($this->ballots->findOpen() as $ballot) {
            if (!$this->isElector($ballot, $viewerUserId)) {
                continue;
            }
            $electable[] = $ballot;
        }

        return $this->visibleOnly($electable, $viewerUserId);
    }

    /**
     * @param  list<Ballot> $ballots
     * @return list<Ballot>
     */
    private function visibleOnly(array $ballots, ?int $viewerUserId): array
    {
        $allowed = [];
        foreach ($this->groupByPurpose($ballots) as [$purpose, $ids]) {
            foreach ($this->visibility->narrow($purpose, $ids, $viewerUserId) as $id) {
                $allowed[$id] = true;
            }
        }

        return array_values(array_filter(
            $ballots,
            static fn(Ballot $ballot): bool => isset($allowed[(int) $ballot->getId()]),
        ));
    }

    /**
     * @param  list<Ballot>                    $ballots
     * @return list<array{string, list<int>}>
     */
    private function groupByPurpose(array $ballots): array
    {
        $groups = [];
        foreach ($ballots as $ballot) {
            $purpose = $ballot->getPurpose();
            $groups[$purpose] ??= [$purpose, []];
            $groups[$purpose][1][] = (int) $ballot->getId();
        }

        return array_values($groups);
    }

    private function mustFind(int $ballotId): Ballot
    {
        $ballot = $this->ballots->find($ballotId);
        if (!$ballot instanceof Ballot) {
            throw new InvalidArgumentException('Unknown ballot.');
        }

        return $ballot;
    }

    private function userReference(int $userId): User
    {
        return $this->entityManager->getReference(User::class, $userId);
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}

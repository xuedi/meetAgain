<?php declare(strict_types=1);

namespace Plugin\Photos\Service;

use App\Item\FilterService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Module\Ballot\Contract\BallotInterface;
use Module\Ballot\Contract\BallotRequest;
use Module\Ballot\Contract\BallotStatus;
use Module\Ballot\Contract\BallotView;
use Module\Ballot\Contract\Candidate;
use Module\Ballot\Contract\SettlementMode;
use Module\Ballot\Contract\TallyMode;
use Plugin\Photos\Entity\Photo;
use Plugin\Photos\Repository\PhotoRepository;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class ContestService
{
    public const string PURPOSE = 'photo.contest';
    public const int DURATION_DAYS = 14;
    private const int MINIMUM_ENTRIES = 2;
    private const int DEMO_PHOTOS_NEEDED = 8;
    private const int DEMO_VOTERS_NEEDED = 3;
    private const int DEMO_ROUND = 3;
    private const int DEMO_FINISHED_MAX = 2;
    private const int DEMO_OPEN_MIN = 3;
    private const int DEMO_OPEN_MAX = 4;
    private const int DEMO_QUEUED = 2;

    public function __construct(
        private PhotoRepository $photoRepo,
        private ConfigService $configService,
        private FilterService $itemFilter,
        private BallotInterface $ballots,
        private TranslatorInterface $translator,
        private EntityManagerInterface $em,
    ) {}

    public function isLive(): bool
    {
        return $this->configService->getConfig()->isContest();
    }

    public function submit(Photo $photo): void
    {
        if ($photo->isContestSubmitted()) {
            return;
        }

        if ($this->remainingFor((int) $photo->getCreatedBy()) < 1) {
            throw new RuntimeException('photos_contest.flash_cap_reached');
        }

        $photo->setContestSubmitted(true);
        $this->em->flush();
    }

    public function withdraw(Photo $photo): void
    {
        $photo->setContestSubmitted(false);
        $this->em->flush();
    }

    public function remainingFor(int $userId): int
    {
        $submitted = $this->photoRepo->countSubmittedByCreator($userId, $this->allowedIds());

        return max(0, $this->configService->getConfig()->getContestSubmissionsPerMember() - $submitted);
    }

    /** @return list<int> */
    public function getQueuedIds(): array
    {
        return $this->photoRepo->findSubmittedIds($this->allowedIds());
    }

    public function getOpenContest(?int $viewerUserId = null): ?BallotView
    {
        foreach ($this->contests($viewerUserId) as $contest) {
            if (!$contest->status->isResolved()) {
                return $contest;
            }
        }

        return null;
    }

    /** @return list<BallotView> */
    public function getFinishedContests(?int $viewerUserId = null): array
    {
        return array_values(array_filter(
            $this->contests($viewerUserId),
            static fn(BallotView $contest): bool => $contest->status === BallotStatus::Settled,
        ));
    }

    public function start(int $createdBy): int
    {
        if ($this->getOpenContest() instanceof BallotView) {
            throw new RuntimeException('photos_contest.flash_already_open');
        }

        $queued = $this->getQueuedIds();
        if ($queued === []) {
            throw new RuntimeException('photos_contest.flash_no_entries');
        }
        if (count($queued) < self::MINIMUM_ENTRIES) {
            throw new RuntimeException('photos_contest.flash_too_few_entries');
        }

        $ballotId = $this->ballots->open($this->request($queued, $createdBy));
        $this->photoRepo->clearSubmitted($queued);

        return $ballotId;
    }

    public function isSeedable(): bool
    {
        return $this->getOpenContest() === null && $this->getFinishedContests() === [];
    }

    /** @param list<int> $photoIds */
    public function seedDemo(array $photoIds): bool
    {
        $voters = $this->distinctCreators($photoIds);
        if (count($photoIds) < self::DEMO_PHOTOS_NEEDED || count($voters) < self::DEMO_VOTERS_NEEDED) {
            return false;
        }

        $rest = array_slice($photoIds, 0, -self::DEMO_QUEUED);
        $rounds = min(self::DEMO_FINISHED_MAX, intdiv(count($rest) - self::DEMO_OPEN_MIN, self::DEMO_ROUND));
        for ($round = 0; $round < $rounds; $round++) {
            $this->seedFinished(array_slice($rest, $round * self::DEMO_ROUND, self::DEMO_ROUND), $voters);
        }

        $this->seedOpen(array_slice($rest, $rounds * self::DEMO_ROUND, self::DEMO_OPEN_MAX), $voters);
        $this->seedQueue(array_slice($photoIds, -self::DEMO_QUEUED));

        return true;
    }

    /**
     * @param list<int> $options
     * @param list<int> $voters
     */
    private function seedFinished(array $options, array $voters): void
    {
        $ballotId = $this->ballots->open($this->request($options, $voters[0]));

        $runnerUp = array_key_last($voters);
        foreach ($voters as $index => $userId) {
            $this->ballots->cast($ballotId, $userId, [(string) $options[$index === $runnerUp ? 1 : 0]]);
        }

        $outcome = $this->ballots->tally($ballotId);
        if ($outcome->winningKey === null) {
            return;
        }

        $this->ballots->settle($ballotId, $outcome->winningKey, $voters[0]);
    }

    /**
     * @param list<int> $options
     * @param list<int> $voters
     */
    private function seedOpen(array $options, array $voters): void
    {
        $ballotId = $this->ballots->open($this->request($options, $voters[0]));

        foreach (array_slice($voters, 0, 3) as $index => $userId) {
            $this->ballots->cast($ballotId, $userId, [(string) $options[$index === 0 ? 0 : 1]]);
        }
    }

    /** @param list<int> $photoIds */
    private function seedQueue(array $photoIds): void
    {
        foreach ($photoIds as $photoId) {
            $photo = $this->photoRepo->find($photoId);
            if ($photo instanceof Photo) {
                $photo->setContestSubmitted(true);
            }
        }

        $this->em->flush();
    }

    /**
     * @return list<BallotView>
     */
    private function contests(?int $viewerUserId): array
    {
        return $this->ballots->listForPurpose(self::PURPOSE, $viewerUserId);
    }

    /**
     * @param list<int> $photoIds
     */
    private function request(array $photoIds, int $createdBy): BallotRequest
    {
        $candidates = [];
        foreach ($photoIds as $photoId) {
            $candidates[] = new Candidate((string) $photoId, '#' . $photoId);
        }

        return new BallotRequest(
            self::PURPOSE,
            $candidates,
            new DateTimeImmutable('+' . self::DURATION_DAYS . ' days'),
            $createdBy,
            null,
            TallyMode::Single,
            SettlementMode::Automatic,
            $this->translator->trans('photos_contest.ballot_title'),
        );
    }

    /**
     * @param  list<int> $photoIds
     * @return list<int> distinct uploader ids, in the order their photos appear
     */
    private function distinctCreators(array $photoIds): array
    {
        $creators = [];
        foreach ($photoIds as $photoId) {
            $createdBy = $this->photoRepo->find($photoId)?->getCreatedBy();
            if ($createdBy !== null) {
                $creators[$createdBy] = true;
            }
        }

        return array_map(intval(...), array_keys($creators));
    }

    /** @return list<int>|null */
    private function allowedIds(): ?array
    {
        return $this->itemFilter->getAllowedItemIds(PhotoService::ITEM_TYPE);
    }
}

<?php declare(strict_types=1);

namespace App\Suggestion;

use App\Activity\ActivityService;
use App\Activity\Messages\SuggestionApproved;
use App\Activity\Messages\SuggestionCreated;
use App\Activity\Messages\SuggestionRejected;
use App\Entity\Suggestion;
use App\Entity\User;
use App\Enum\SuggestionStatus;
use App\Repository\SuggestionRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

readonly class SuggestionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SuggestionRepository $repo,
        private SuggestionRegistry $registry,
        private ActivityService $activityService,
    ) {}

    public function propose(string $targetType, User $proposer, object $draft): Suggestion
    {
        $provider = $this->providerFor($targetType);

        if (!$provider->canPropose($proposer)) {
            throw new AccessDeniedException('Not allowed to suggest this target type.');
        }

        $error = $provider->validate($draft);
        if ($error !== null) {
            throw new SuggestionException($error);
        }

        $suggestion = new Suggestion();
        $suggestion->setTargetType($targetType);
        $suggestion->setProposedBy($proposer);
        $suggestion->setPayload($provider->toPayload($draft));

        $this->em->persist($suggestion);
        $this->em->flush();

        $this->activityService->log(SuggestionCreated::TYPE, $proposer, $this->activityMeta($suggestion));

        return $suggestion;
    }

    public function approve(Suggestion $suggestion, object $editedDraft, User $reviewer): int
    {
        $provider = $this->reviewableProvider($suggestion, $reviewer);
        $this->ensurePending($suggestion);

        $error = $provider->validate($editedDraft);
        if ($error !== null) {
            throw new SuggestionException($error);
        }

        $createdId = $provider->create($editedDraft, $suggestion->getProposedBy());

        $suggestion->setPayload($provider->toPayload($editedDraft));
        $suggestion->setCreatedId($createdId);
        $this->resolve($suggestion, $reviewer, SuggestionStatus::Approved);

        $this->activityService->log(SuggestionApproved::TYPE, $reviewer, $this->activityMeta($suggestion));

        return $createdId;
    }

    public function reject(Suggestion $suggestion, User $reviewer): void
    {
        $this->reviewableProvider($suggestion, $reviewer);
        $this->ensurePending($suggestion);

        $this->resolve($suggestion, $reviewer, SuggestionStatus::Rejected);

        $this->activityService->log(SuggestionRejected::TYPE, $reviewer, $this->activityMeta($suggestion));
    }

    public function withdraw(Suggestion $suggestion, User $user): void
    {
        if ($suggestion->getProposedBy()->getId() !== $user->getId()) {
            throw new AccessDeniedException('Only the proposer can withdraw a suggestion.');
        }
        $this->ensurePending($suggestion);

        $this->resolve($suggestion, null, SuggestionStatus::Withdrawn);
    }

    public function get(int $id): ?Suggestion
    {
        return $this->repo->find($id);
    }

    /** @return list<Suggestion> */
    public function pendingReviewableBy(User $user): array
    {
        $reviewable = [];
        foreach ($this->repo->findPending() as $suggestion) {
            $provider = $this->registry->providerFor($suggestion->getTargetType());
            if ($provider === null || !$provider->canReview($user)) {
                continue;
            }

            $reviewable[] = $suggestion;
        }

        return $reviewable;
    }

    /** @return list<Suggestion> */
    public function pendingFor(User $proposer): array
    {
        $own = [];
        foreach ($this->repo->findPendingByProposer($proposer) as $suggestion) {
            if (!$this->registry->has($suggestion->getTargetType())) {
                continue;
            }

            $own[] = $suggestion;
        }

        return $own;
    }

    public function removeForTargetType(string $targetType): void
    {
        $this->repo->removeForTargetType($targetType);
    }

    public function hasProvider(string $targetType): bool
    {
        return $this->registry->has($targetType);
    }

    public function canReviewTargetType(string $targetType, User $user): bool
    {
        return $this->registry->providerFor($targetType)?->canReview($user) === true;
    }

    public function draftFor(Suggestion $suggestion): object
    {
        return $this->providerFor($suggestion->getTargetType())->fromPayload($suggestion->getPayload());
    }

    public function describe(Suggestion $suggestion): string
    {
        return $this->registry->providerFor($suggestion->getTargetType())?->describe($suggestion->getPayload()) ?? '';
    }

    /** @return list<array{label: string, value: string}> */
    public function summaryRows(Suggestion $suggestion): array
    {
        return $this->registry->providerFor($suggestion->getTargetType())?->summaryRows($suggestion->getPayload()) ?? [];
    }

    public function providerFor(string $targetType): SuggestionTargetProviderInterface
    {
        $provider = $this->registry->providerFor($targetType);
        if ($provider === null) {
            throw new InvalidArgumentException(sprintf('No active suggestion target registered for type "%s"', $targetType));
        }

        return $provider;
    }

    private function reviewableProvider(Suggestion $suggestion, User $reviewer): SuggestionTargetProviderInterface
    {
        $provider = $this->providerFor($suggestion->getTargetType());
        if (!$provider->canReview($reviewer)) {
            throw new AccessDeniedException('Not allowed to review suggestions of this target type.');
        }

        return $provider;
    }

    private function ensurePending(Suggestion $suggestion): void
    {
        if (!$suggestion->isPending()) {
            throw new SuggestionException('review_suggestion.flash_not_pending');
        }
    }

    private function resolve(Suggestion $suggestion, ?User $reviewer, SuggestionStatus $status): void
    {
        $suggestion->setStatus($status);
        $suggestion->setReviewedBy($reviewer);
        $suggestion->setResolvedAt(new DateTimeImmutable());
        $this->em->flush();
    }

    /** @return array{target_type: string, created_id: ?int, description: string} */
    private function activityMeta(Suggestion $suggestion): array
    {
        return [
            'target_type' => $suggestion->getTargetType(),
            'created_id' => $suggestion->getCreatedId(),
            'description' => $this->describe($suggestion),
        ];
    }
}

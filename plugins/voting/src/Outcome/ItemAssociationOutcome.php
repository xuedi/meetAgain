<?php declare(strict_types=1);

namespace Plugin\Voting\Outcome;

use App\Item\TypeRegistry;
use App\Service\Item\AssociationService;
use Override;
use Plugin\Voting\Entity\Poll;

final readonly class ItemAssociationOutcome implements PollOutcomeProviderInterface
{
    public function __construct(
        private TypeRegistry $registry,
        private AssociationService $associations,
    ) {}

    #[Override]
    public function supports(string $itemType): bool
    {
        return $this->registry->has($itemType);
    }

    #[Override]
    public function getLabelKey(string $itemType): ?string
    {
        return $this->registry->providerFor($itemType)?->getLabelKey();
    }

    #[Override]
    public function commit(Poll $poll, int $chosenItemId): void
    {
        $eventId = $poll->getEventId();
        if ($eventId === null) {
            return;
        }

        $this->associations->attach($eventId, (string) $poll->getItemType(), $chosenItemId, (int) $poll->getCreatedBy());
    }

    #[Override]
    public function getPriority(): int
    {
        return 100;
    }
}

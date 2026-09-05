<?php declare(strict_types=1);

namespace Plugin\Voting\Outcome;

use Plugin\Voting\Entity\Poll;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Turns a poll's winner into whatever that subject means. First-match-wins chain: the registry
 * asks each provider in priority order whether it supports the poll's item type.
 */
#[AutoconfigureTag]
interface PollOutcomeProviderInterface
{
    public function supports(string $itemType): bool;

    /** Translation key for the subject's label; null when the type has no label of its own. */
    public function getLabelKey(string $itemType): ?string;

    public function commit(Poll $poll, int $chosenItemId): void;

    /** Ascending; the lowest number is asked first. */
    public function getPriority(): int;
}

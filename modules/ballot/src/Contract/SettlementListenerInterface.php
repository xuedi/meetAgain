<?php declare(strict_types=1);

namespace Module\Ballot\Contract;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Turns a settled ballot into a real change. First match on the purpose wins; a purpose nobody
 * claims settles and writes nothing.
 */
#[AutoconfigureTag]
interface SettlementListenerInterface
{
    /** Higher priority runs first. Default: 0. */
    public function getPriority(): int;

    public function supports(string $purpose): bool;

    public function settled(BallotOutcome $outcome): void;
}

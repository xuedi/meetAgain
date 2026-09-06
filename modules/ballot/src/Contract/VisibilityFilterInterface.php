<?php declare(strict_types=1);

namespace Module\Ballot\Contract;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Narrows which ballots a viewer may see. Implementations compose with AND-intersection.
 */
#[AutoconfigureTag]
interface VisibilityFilterInterface
{
    /** Higher priority runs first. Default: 0. */
    public function getPriority(): int;

    /**
     * @param  list<BallotScope> $ballots  every candidate of the one purpose, subject and candidate
     *                                     keys included so a filter never has to read module tables
     * @return list<int>|null    null = no opinion, [] = block all, [id, ...] = the visible subset
     */
    public function narrowVisibleBallotIds(string $purpose, array $ballots, ?int $viewerUserId): ?array;
}

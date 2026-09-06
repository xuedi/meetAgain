<?php declare(strict_types=1);

namespace App\Contribution;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Narrows which rows of one type a member is offered. Implementations compose with AND-intersection.
 */
#[AutoconfigureTag]
interface ScopeFilterInterface
{
    /**
     * Higher priority runs first. Default: 0.
     */
    public function getPriority(): int;

    /**
     * @param  list<int|string>      $ids
     * @return list<int|string>|null null = no opinion, [] = block all, [id, ...] = the reachable subset
     */
    public function narrowContributableIds(string $type, array $ids, User $user): ?array;
}

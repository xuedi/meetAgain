<?php declare(strict_types=1);

namespace Module\Ballot\Contract;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Answers who may vote on a purpose. First match wins; with no provider every authenticated member
 * may vote.
 */
#[AutoconfigureTag]
interface ElectorateProviderInterface
{
    public function supports(string $purpose): bool;

    public function mayVote(int $userId, ?BallotSubject $subject): bool;
}

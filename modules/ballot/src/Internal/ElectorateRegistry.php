<?php declare(strict_types=1);

namespace Module\Ballot\Internal;

use Module\Ballot\Contract\BallotSubject;
use Module\Ballot\Contract\ElectorateProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ElectorateRegistry
{
    /**
     * @param iterable<ElectorateProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator(ElectorateProviderInterface::class)]
        private iterable $providers,
    ) {}

    public function mayVote(string $purpose, int $userId, ?BallotSubject $subject): bool
    {
        if ($userId <= 0) {
            return false;
        }

        foreach ($this->providers as $provider) {
            if ($provider->supports($purpose)) {
                return $provider->mayVote($userId, $subject);
            }
        }

        return true;
    }
}

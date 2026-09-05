<?php declare(strict_types=1);

namespace Plugin\Voting\Outcome;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class OutcomeRegistry
{
    /**
     * @param iterable<PollOutcomeProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator(PollOutcomeProviderInterface::class)]
        private iterable $providers,
    ) {}

    public function supports(string $itemType): bool
    {
        return $this->providerFor($itemType) !== null;
    }

    public function providerFor(string $itemType): ?PollOutcomeProviderInterface
    {
        foreach ($this->sorted() as $provider) {
            if ($provider->supports($itemType)) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * @return list<PollOutcomeProviderInterface>
     */
    private function sorted(): array
    {
        $providers = iterator_to_array($this->providers, false);
        usort($providers, static fn(PollOutcomeProviderInterface $a, PollOutcomeProviderInterface $b): int => $a->getPriority() <=> $b->getPriority());

        return array_values($providers);
    }
}

<?php declare(strict_types=1);

namespace App\Event;

use App\Entity\Event;
use App\Repository\LocationRepository;
use App\Service\Config\PluginService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class LocationChoiceService
{
    private const string CORE_PLUGIN_KEY = '';

    /**
     * @param iterable<LocationChoiceProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator(LocationChoiceProviderInterface::class)]
        private readonly iterable $providers,
        private readonly PluginService $pluginService,
        private readonly LocationRepository $locationRepo,
    ) {}

    public function apply(Event $event, string $choice): ?LocationChoiceProviderInterface
    {
        $provider = $this->providerFor($event->getId() === null ? null : $event, $choice);
        if ($provider === null) {
            $event->setLocation($this->locationRepo->find((int) $choice));
        }

        return $provider;
    }

    /**
     * @param array<string, mixed> $terms
     */
    public function commit(Event $event, ?LocationChoiceProviderInterface $provider, array $terms = []): void
    {
        if ($provider === null) {
            $this->releaseAll($event);

            return;
        }

        $provider->choose($event, $terms);
    }

    /**
     * @return list<LocationChoiceProviderInterface>
     */
    public function availableFor(?Event $event): array
    {
        $enabledPlugins = $this->pluginService->getActiveList();
        $available = [];
        foreach ($this->providers as $provider) {
            $pluginKey = $provider->getPluginKey();
            if ($pluginKey !== self::CORE_PLUGIN_KEY && !in_array($pluginKey, $enabledPlugins, true)) {
                continue;
            }
            if (!$provider->isAvailableFor($event)) {
                continue;
            }

            $available[] = $provider;
        }

        return $available;
    }

    public function activeValueFor(Event $event): ?string
    {
        foreach ($this->availableFor($event) as $provider) {
            if ($provider->isActiveFor($event)) {
                return $provider->getValue();
            }
        }

        return null;
    }

    public function providerFor(?Event $event, string $value): ?LocationChoiceProviderInterface
    {
        foreach ($this->availableFor($event) as $provider) {
            if ($provider->getValue() === $value) {
                return $provider;
            }
        }

        return null;
    }

    public function releaseAll(Event $event): void
    {
        foreach ($this->availableFor($event) as $provider) {
            if (!$provider->isActiveFor($event)) {
                continue;
            }

            $provider->release($event);
        }
    }
}

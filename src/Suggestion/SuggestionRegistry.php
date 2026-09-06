<?php declare(strict_types=1);

namespace App\Suggestion;

use App\Service\Config\PluginService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class SuggestionRegistry
{
    private const string CORE_PLUGIN_KEY = '';

    /**
     * @var array<string, SuggestionTargetProviderInterface>|null
     */
    private ?array $active = null;

    /**
     * @param iterable<SuggestionTargetProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator(SuggestionTargetProviderInterface::class)]
        private readonly iterable $providers,
        private readonly PluginService $pluginService,
    ) {}

    public function has(string $targetType): bool
    {
        return isset($this->getActive()[$targetType]);
    }

    public function providerFor(string $targetType): ?SuggestionTargetProviderInterface
    {
        return $this->getActive()[$targetType] ?? null;
    }

    /**
     * @return array<string, SuggestionTargetProviderInterface>
     */
    private function getActive(): array
    {
        if ($this->active !== null) {
            return $this->active;
        }

        $enabledPlugins = $this->pluginService->getGloballyActiveList();
        $map = [];
        foreach ($this->providers as $provider) {
            $pluginKey = $provider->getPluginKey();
            if ($pluginKey !== self::CORE_PLUGIN_KEY && !in_array($pluginKey, $enabledPlugins, true)) {
                continue;
            }

            $map[$provider->getTargetType()] = $provider;
        }

        return $this->active = $map;
    }
}

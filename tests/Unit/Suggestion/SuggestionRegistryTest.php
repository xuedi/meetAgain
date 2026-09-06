<?php declare(strict_types=1);

namespace Tests\Unit\Suggestion;

use App\Service\Config\PluginService;
use App\Suggestion\SuggestionRegistry;
use App\Suggestion\SuggestionTargetProviderInterface;
use PHPUnit\Framework\TestCase;

class SuggestionRegistryTest extends TestCase
{
    public function testACoreProviderIsActiveWithoutAppearingInThePluginList(): void
    {
        // Arrange
        $provider = $this->provider('', 'location');
        $registry = $this->makeRegistry([$provider], ['glossary']);

        // Act
        $found = $registry->providerFor('location');

        // Assert
        self::assertSame($provider, $found);
        self::assertTrue($registry->has('location'));
    }

    public function testAProviderOfAnActivePluginIsFoundByTargetType(): void
    {
        // Arrange
        $provider = $this->provider('glossary', 'glossary');
        $registry = $this->makeRegistry([$provider], ['glossary']);

        // Act
        $found = $registry->providerFor('glossary');

        // Assert
        self::assertSame($provider, $found);
    }

    public function testAProviderOfAnInactivePluginIsHidden(): void
    {
        // Arrange
        $registry = $this->makeRegistry([$this->provider('glossary', 'glossary')], ['dishes']);

        // Act
        $found = $registry->providerFor('glossary');

        // Assert
        self::assertNull($found);
        self::assertFalse($registry->has('glossary'));
    }

    public function testAnUnknownTargetTypeHasNoProvider(): void
    {
        // Arrange
        $registry = $this->makeRegistry([$this->provider('', 'location')], []);

        // Act
        $found = $registry->providerFor('book');

        // Assert
        self::assertNull($found);
    }

    /**
     * @param list<SuggestionTargetProviderInterface> $providers
     * @param list<string>                            $activePlugins
     */
    private function makeRegistry(array $providers, array $activePlugins): SuggestionRegistry
    {
        $pluginService = $this->createStub(PluginService::class);
        $pluginService->method('getGloballyActiveList')->willReturn($activePlugins);

        return new SuggestionRegistry($providers, $pluginService);
    }

    private function provider(string $pluginKey, string $targetType): SuggestionTargetProviderInterface
    {
        $provider = $this->createStub(SuggestionTargetProviderInterface::class);
        $provider->method('getPluginKey')->willReturn($pluginKey);
        $provider->method('getTargetType')->willReturn($targetType);

        return $provider;
    }
}

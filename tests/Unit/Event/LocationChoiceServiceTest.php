<?php declare(strict_types=1);

namespace Tests\Unit\Event;

use App\Entity\Event;
use App\Event\LocationChoiceProviderInterface;
use App\Event\LocationChoiceService;
use App\Repository\LocationRepository;
use App\Service\Config\PluginService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocationChoiceServiceTest extends TestCase
{
    public function testAnEntryFromAnInactivePluginIsNotOffered(): void
    {
        // Arrange
        $service = $this->makeService([$this->provider('films:pick_the_film', 'films')], activePlugins: []);

        // Act & Assert
        self::assertSame([], $service->availableFor(null));
    }

    public function testACoreEntryIsOfferedWithoutAnyPluginBeingActive(): void
    {
        // Arrange
        $provider = $this->provider('core:something', '');
        $service = $this->makeService([$provider], activePlugins: []);

        // Act & Assert
        self::assertSame([$provider], $service->availableFor(null));
    }

    public function testAProviderThatDeclaresItselfUnavailableIsNotOffered(): void
    {
        // Arrange
        $service = $this->makeService([$this->provider('films:pick_the_film', 'films', available: false)]);

        // Act & Assert
        self::assertSame([], $service->availableFor(null));
    }

    public function testTheActiveEntryIsWhatPreselectsTheDropdown(): void
    {
        // Arrange
        $service = $this->makeService([$this->provider('films:pick_the_film', 'films', active: true)]);

        // Act & Assert
        self::assertSame('films:pick_the_film', $service->activeValueFor(new Event()));
    }

    public function testAnEntryNobodyClaimsResolvesToNoProvider(): void
    {
        // Arrange
        $service = $this->makeService([$this->provider('films:pick_the_film', 'films')]);

        // Act & Assert
        self::assertNull($service->providerFor(null, '17'));
    }

    public function testReleaseAllOnlyTouchesTheProviderThatIsActive(): void
    {
        // Arrange
        $idle = $this->providerMock('a:idle', 'films');
        $idle->expects(self::never())->method('release');
        $running = $this->providerMock('b:running', 'films', active: true);
        $running->expects(self::once())->method('release');
        $service = $this->makeService([$idle, $running]);

        // Act
        $service->releaseAll(new Event());
    }

    /**
     * @param list<LocationChoiceProviderInterface> $providers
     * @param list<string> $activePlugins
     */
    private function makeService(array $providers, array $activePlugins = ['films']): LocationChoiceService
    {
        $pluginService = $this->createStub(PluginService::class);
        $pluginService->method('getActiveList')->willReturn($activePlugins);

        return new LocationChoiceService($providers, $pluginService, $this->createStub(LocationRepository::class));
    }

    private function provider(string $value, string $pluginKey, bool $available = true, bool $active = false): LocationChoiceProviderInterface
    {
        $provider = $this->createStub(LocationChoiceProviderInterface::class);
        $provider->method('getValue')->willReturn($value);
        $provider->method('getPluginKey')->willReturn($pluginKey);
        $provider->method('isAvailableFor')->willReturn($available);
        $provider->method('isActiveFor')->willReturn($active);

        return $provider;
    }

    private function providerMock(string $value, string $pluginKey, bool $active = false): MockObject
    {
        $provider = $this->createMock(LocationChoiceProviderInterface::class);
        $provider->method('getValue')->willReturn($value);
        $provider->method('getPluginKey')->willReturn($pluginKey);
        $provider->method('isAvailableFor')->willReturn(true);
        $provider->method('isActiveFor')->willReturn($active);

        return $provider;
    }
}

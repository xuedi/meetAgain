<?php declare(strict_types=1);

namespace Plugin\Voting\Tests\Unit\Outcome;

use PHPUnit\Framework\TestCase;
use Plugin\Voting\Outcome\OutcomeRegistry;
use Plugin\Voting\Outcome\PollOutcomeProviderInterface;

class OutcomeRegistryTest extends TestCase
{
    public function testTheLowestPriorityProviderThatSupportsTheTypeWins(): void
    {
        // Arrange
        $specific = $this->provider(supports: true, priority: 10);
        $fallback = $this->provider(supports: true, priority: 100);
        $registry = new OutcomeRegistry([$fallback, $specific]);

        // Act
        $found = $registry->providerFor('location');

        // Assert
        self::assertSame($specific, $found);
    }

    public function testAProviderThatDoesNotSupportTheTypeIsSkipped(): void
    {
        // Arrange
        $fallback = $this->provider(supports: true, priority: 100);
        $registry = new OutcomeRegistry([$this->provider(supports: false, priority: 10), $fallback]);

        // Act
        $found = $registry->providerFor('film');

        // Assert
        self::assertSame($fallback, $found);
    }

    public function testAnUnsupportedTypeHasNoProvider(): void
    {
        // Arrange
        $registry = new OutcomeRegistry([$this->provider(supports: false, priority: 10)]);

        // Act & Assert
        self::assertNull($registry->providerFor('film'));
        self::assertFalse($registry->supports('film'));
    }

    private function provider(bool $supports, int $priority): PollOutcomeProviderInterface
    {
        $provider = $this->createStub(PollOutcomeProviderInterface::class);
        $provider->method('supports')->willReturn($supports);
        $provider->method('getPriority')->willReturn($priority);

        return $provider;
    }
}

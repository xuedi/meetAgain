<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CheckPluginConfigTest extends TestCase
{
    private string $script;
    private string $fixtureDir;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 2);
        $this->script = $root . '/bin/check-plugin-config.php';
        $this->fixtureDir = __DIR__ . '/CheckPluginConfig';
    }

    public function testCleanInstallExitsZero(): void
    {
        // Arrange
        $root = $this->fixtureDir . '/Clean';

        // Act
        [$exitCode, $output] = $this->runGuard($root);

        // Assert
        self::assertSame(0, $exitCode, 'Clean install must exit 0. Output: ' . $output);
        self::assertStringContainsString('2 installed plugin(s) listed in plugins.php', $output);
    }

    public function testPluginOnDiskWithoutKeyExitsOne(): void
    {
        // Arrange
        $root = $this->fixtureDir . '/Unlisted';

        // Act
        [$exitCode, $output] = $this->runGuard($root);

        // Assert
        self::assertSame(1, $exitCode, 'Unlisted plugin must exit 1. Output: ' . $output);
        self::assertStringContainsString('beta is installed but has no key in plugins.php', $output);
        self::assertStringContainsString("Add the missing line(s):\n    'beta' => true,", $output);
        self::assertStringNotContainsString('Delete the stale line(s):', $output);
    }

    public function testKeyWithoutPluginOnDiskExitsOne(): void
    {
        // Arrange
        $root = $this->fixtureDir . '/Stale';

        // Act
        [$exitCode, $output] = $this->runGuard($root);

        // Assert
        self::assertSame(1, $exitCode, 'Stale key must exit 1. Output: ' . $output);
        self::assertStringContainsString('gone has a key in plugins.php but no plugins/gone/config directory', $output);
        self::assertStringContainsString("Delete the stale line(s):\n    'gone' => false,", $output);
        self::assertStringNotContainsString('Add the missing line(s):', $output);
    }

    public function testUnsatisfiedRequiresExitsOne(): void
    {
        // Arrange
        $root = $this->fixtureDir . '/Unsatisfied';

        // Act
        [$exitCode, $output] = $this->runGuard($root);

        // Assert
        self::assertSame(1, $exitCode, 'Unsatisfied requires must exit 1. Output: ' . $output);
        self::assertStringContainsString('alpha requires beta, which has no key in plugins.php', $output);
        self::assertStringContainsString("Add the missing line(s):\n    'beta' => true,", $output);
    }

    /**
     * @return array{int, string}
     */
    private function runGuard(string $root): array
    {
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($this->script) . ' test ' . escapeshellarg($root);
        $output = [];
        exec($cmd, $output, $exitCode);

        return [$exitCode, implode("\n", $output)];
    }
}

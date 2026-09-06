<?php declare(strict_types=1);

namespace Plugin\Glossary\Tests\Unit\Suggestion;

use PHPUnit\Framework\TestCase;
use Plugin\Glossary\Entity\Glossary;
use Plugin\Glossary\Service\ConfigService;
use Plugin\Glossary\Service\GlossaryService;
use Plugin\Glossary\Suggestion\GlossaryTarget;
use Plugin\Glossary\ValueObject\Config;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class GlossaryTargetTest extends TestCase
{
    public function testAPayloadSurvivesTheRoundTrip(): void
    {
        // Arrange
        $target = $this->target();
        $draft = (new Glossary())->setPhrase('半路出家')->setPinyin('bàn lù chū jiā')->setExplanation('A latecomer to a craft.');

        // Act
        $restored = $target->fromPayload($target->toPayload($draft));

        // Assert
        self::assertInstanceOf(Glossary::class, $restored);
        self::assertSame('半路出家', $restored->getPhrase());
        self::assertSame('bàn lù chū jiā', $restored->getPinyin());
        self::assertSame('A latecomer to a craft.', $restored->getExplanation());
    }

    public function testAnAbsentSecondaryFieldComesBackAsNullRatherThanAnEmptyString(): void
    {
        // Arrange
        $target = $this->target();

        // Act
        $restored = $target->fromPayload(['phrase' => '加油', 'explanation' => 'Keep going.']);

        // Assert
        self::assertInstanceOf(Glossary::class, $restored);
        self::assertNull($restored->getPinyin());
    }

    public function testAPhraseAlreadyInTheGlossaryIsRefused(): void
    {
        // Arrange
        $target = $this->target(existing: ['你好']);

        // Act
        $duplicate = $target->validate((new Glossary())->setPhrase('你好')->setExplanation('Hello.'));
        $fresh = $target->validate((new Glossary())->setPhrase('您好')->setExplanation('Hello, politely.'));

        // Assert
        self::assertSame('glossary.validator_duplicate', $duplicate, 'the same phrase cannot be suggested twice');
        self::assertNull($fresh);
    }

    public function testTheDuplicateGuardIgnoresCaseAndSurroundingSpace(): void
    {
        // Arrange
        $target = $this->target(existing: ['Sobremesa']);

        // Act
        $verdict = $target->validate((new Glossary())->setPhrase(' sobremesa ')->setExplanation('The talk after a meal.'));

        // Assert
        self::assertSame('glossary.validator_duplicate', $verdict);
    }

    public function testAnEntryWithoutAnExplanationIsRefused(): void
    {
        // Arrange
        $target = $this->target();

        // Act & Assert
        self::assertSame('glossary.validator_incomplete', $target->validate((new Glossary())->setPhrase('加油')));
        self::assertSame('glossary.validator_incomplete', $target->validate((new Glossary())->setPhrase(' ')->setExplanation('Keep going.')));
    }

    public function testTheSecondaryRowIsOnlyOfferedWhereTheConfigEnablesIt(): void
    {
        // Arrange
        $withSecondary = $this->target(secondaryEnabled: true);
        $withoutSecondary = $this->target(secondaryEnabled: false);
        $payload = ['phrase' => '你好', 'pinyin' => 'nǐ hǎo', 'explanation' => 'Hello.'];

        // Act
        $labelled = array_column($withSecondary->summaryRows($payload), 'value');
        $plain = array_column($withoutSecondary->summaryRows($payload), 'value');

        // Assert
        self::assertSame(['你好', 'nǐ hǎo', 'Hello.'], $labelled);
        self::assertSame(['你好', 'Hello.'], $plain);
    }

    /**
     * @param list<string> $existing
     */
    private function target(array $existing = [], bool $secondaryEnabled = true): GlossaryTarget
    {
        $entries = array_map(static fn(string $phrase): Glossary => (new Glossary())->setPhrase($phrase), $existing);

        $service = $this->createStub(GlossaryService::class);
        $service->method('getList')->willReturn($entries);

        $config = (new Config())->setSecondaryEnabled($secondaryEnabled);
        $configService = $this->createStub(ConfigService::class);
        $configService->method('getConfig')->willReturn($config);

        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn(true);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return new GlossaryTarget($service, $configService, $security, $translator);
    }
}

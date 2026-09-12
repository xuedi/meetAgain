<?php declare(strict_types=1);

namespace Plugin\Glossary\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plugin\Glossary\Enum\MatchResult;
use Plugin\Glossary\Service\AnswerMatcher;

class AnswerMatcherTest extends TestCase
{
    #[DataProvider('answerCases')]
    public function testMatch(string $given, string $expected, MatchResult $result): void
    {
        // Arrange
        $matcher = new AnswerMatcher();

        // Act
        $actual = $matcher->match($given, $expected);

        // Assert
        self::assertSame($result, $actual);
    }

    public static function answerCases(): iterable
    {
        yield 'the same word is exact' => ['hello', 'hello', MatchResult::Exact];
        yield 'case and surrounding space are ignored' => ['  Hello ', 'hello', MatchResult::Exact];
        yield 'inner whitespace collapses' => ['good   morning', 'good morning', MatchResult::Exact];
        yield 'any alternative after a semicolon counts' => ['hi', 'hello; hi', MatchResult::Exact];
        yield 'any alternative after a slash counts' => ['hi', 'hello / hi', MatchResult::Exact];
        yield 'chinese characters match exactly' => ['你好', '你好', MatchResult::Exact];
        yield 'missing tone marks are a near miss' => ['ni hao', 'nǐ hǎo', MatchResult::NearMiss];
        yield 'missing accents are a near miss' => ['cafe', 'café', MatchResult::NearMiss];
        yield 'one wrong letter is a near miss' => ['helo', 'hello', MatchResult::NearMiss];
        yield 'one wrong character in a short answer is wrong' => ['你们', '你好', MatchResult::Wrong];
        yield 'a different word is wrong' => ['bye', 'hello', MatchResult::Wrong];
        yield 'an empty answer is wrong' => ['  ', 'hello', MatchResult::Wrong];
    }

    public function testWithoutFoldingAMissingToneMarkIsWrong(): void
    {
        // Arrange
        $matcher = new AnswerMatcher();

        // Act
        $result = $matcher->match('ni hao', 'nǐ hǎo', foldDiacritics: false);

        // Assert
        self::assertSame(MatchResult::Wrong, $result);
    }

    public function testSameComparesNormalisedText(): void
    {
        // Arrange
        $matcher = new AnswerMatcher();

        // Act & Assert
        self::assertTrue($matcher->same(' Hello  World ', 'hello world'));
        self::assertFalse($matcher->same('hello', 'hallo'));
    }
}

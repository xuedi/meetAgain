<?php declare(strict_types=1);

namespace Plugin\Glossary\Tests\Unit\ValueObject;

use PHPUnit\Framework\TestCase;
use Plugin\Glossary\Enum\AnswerMode;
use Plugin\Glossary\Enum\Direction;
use Plugin\Glossary\Enum\Mode;
use Plugin\Glossary\Enum\Scope;
use Plugin\Glossary\ValueObject\TrainerSession;

class TrainerSessionTest extends TestCase
{
    public function testAnsweringMovesPastTheEntryAndKeepsScore(): void
    {
        // Arrange
        $session = $this->session([4, 7, 9]);

        // Act
        $after = $session->withAnswer(4, true, $this->feedback())->withAnswer(7, false, $this->feedback());

        // Assert
        self::assertSame([9], $after->queue);
        self::assertSame(2, $after->answered);
        self::assertSame(1, $after->correct);
        self::assertSame([7], $after->missed);
        self::assertFalse($after->isFinished());
    }

    public function testAnsweringAnEntryFurtherDownDropsTheSkippedOnesBeforeIt(): void
    {
        // Arrange
        $session = $this->session([4, 7, 9]);

        // Act
        $after = $session->withAnswer(7, true, $this->feedback());

        // Assert
        self::assertSame([9], $after->queue);
    }

    public function testTheStoredShapeRoundTrips(): void
    {
        // Arrange
        $session = $this->session([4, 7])->withAnswer(4, false, $this->feedback());

        // Act
        $restored = TrainerSession::fromArray($session->toArray());

        // Assert
        self::assertEquals($session, $restored);
    }

    public function testAMalformedStoredSessionIsDiscarded(): void
    {
        // Act & Assert
        self::assertNull(TrainerSession::fromArray(null));
        self::assertNull(TrainerSession::fromArray('queue'));
        self::assertNull(TrainerSession::fromArray(['scope' => 'everything', 'mode' => 'review']));
    }

    /** @param list<int> $queue */
    private function session(array $queue): TrainerSession
    {
        return new TrainerSession(Scope::Selection, [3], Mode::Review, Direction::TermToDefinition, AnswerMode::Flip, $queue, 42, count($queue));
    }

    /** @return array{verdict: string, prompt: string, expected: string, given: ?string} */
    private function feedback(): array
    {
        return ['verdict' => 'exact', 'prompt' => '你好', 'expected' => 'Hello', 'given' => null];
    }
}

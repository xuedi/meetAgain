<?php declare(strict_types=1);

namespace Plugin\Glossary\Tests\Unit\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Plugin\Glossary\Entity\Glossary;
use Plugin\Glossary\Entity\TrainerCard;
use Plugin\Glossary\Enum\CardState;
use Plugin\Glossary\Enum\Direction;
use Plugin\Glossary\Enum\Grade;
use Plugin\Glossary\Service\Sm2Scheduler;

class Sm2SchedulerTest extends TestCase
{
    public function testAKnownGradeSequenceProducesAKnownIntervalSequence(): void
    {
        // Arrange
        $scheduler = new Sm2Scheduler();
        $card = $this->card();
        $intervals = [];

        // Act
        foreach ([Grade::Good, Grade::Good, Grade::Good, Grade::Easy, Grade::Hard] as $grade) {
            $scheduler->schedule($card, $grade, $this->now());
            $intervals[] = $card->getIntervalDays();
        }

        // Assert
        self::assertSame([1, 6, 15, 38, 99], $intervals);
        self::assertSame(2460, $card->getEasePermille());
        self::assertSame(5, $card->getRepetitions());
        self::assertSame(CardState::Review, $card->getState());
    }

    public function testAgainOnALearnedCardStartsOverAndCountsALapse(): void
    {
        // Arrange
        $scheduler = new Sm2Scheduler();
        $card = $this->card();
        $scheduler->schedule($card, Grade::Good, $this->now());
        $scheduler->schedule($card, Grade::Good, $this->now());

        // Act
        $scheduler->schedule($card, Grade::Again, $this->now());

        // Assert
        self::assertSame(0, $card->getRepetitions());
        self::assertSame(1, $card->getIntervalDays());
        self::assertSame(1, $card->getLapses());
        self::assertSame(2180, $card->getEasePermille());
        self::assertSame(CardState::Relearning, $card->getState());
    }

    public function testAgainOnAnUnlearnedCardIsNoLapse(): void
    {
        // Arrange
        $scheduler = new Sm2Scheduler();
        $card = $this->card();

        // Act
        $scheduler->schedule($card, Grade::Again, $this->now());

        // Assert
        self::assertSame(0, $card->getLapses());
        self::assertSame(CardState::Learning, $card->getState());
    }

    public function testTheEaseNeverDropsBelowTheFloor(): void
    {
        // Arrange
        $scheduler = new Sm2Scheduler();
        $card = $this->card();

        // Act
        for ($i = 0; $i < 10; ++$i) {
            $scheduler->schedule($card, Grade::Again, $this->now());
        }

        // Assert
        self::assertSame(1300, $card->getEasePermille());
    }

    public function testTheCardFallsDueOneIntervalAfterTheAnswer(): void
    {
        // Arrange
        $scheduler = new Sm2Scheduler();
        $card = $this->card();
        $scheduler->schedule($card, Grade::Good, $this->now());

        // Act
        $scheduler->schedule($card, Grade::Good, $this->now());

        // Assert
        self::assertSame('2026-01-07 10:00:00', $card->getDueAt()?->format('Y-m-d H:i:s'));
    }

    private function card(): TrainerCard
    {
        return new TrainerCard(1, new Glossary(), Direction::TermToDefinition, $this->now());
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-01 10:00:00');
    }
}

<?php declare(strict_types=1);

namespace Plugin\Glossary\Service;

use DateTimeImmutable;
use Plugin\Glossary\Entity\TrainerCard;
use Plugin\Glossary\Enum\Grade;

/**
 * Moves a card's schedule forward after one graded answer. Implementations own the card's state,
 * due date, interval, ease, repetitions and lapses; answer counters belong to the caller.
 */
interface SchedulerInterface
{
    public function schedule(TrainerCard $card, Grade $grade, DateTimeImmutable $now): void;
}

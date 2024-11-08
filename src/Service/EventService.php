<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Event;
use App\Entity\EventIntervals;
use App\Repository\EventRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use RRule\RRule;
use RuntimeException;

class EventService
{
    public function __construct(
        private readonly EventRepository $repo,
        private readonly EntityManagerInterface $em
    ) {
    }

    public function getList(bool $pastEvents = false): array
    {
        $structuredList = [];


        if ($pastEvents) {
            //
        }

        $events = $this->repo->findBy([], ['start' => 'ASC']);
        foreach ($events as $event) {
            $key = $event->getStart()->format('Y-m');
            if (!isset($structuredList[$key])) {
                $structuredList[$key] = [
                    'year' => $event->getStart()->format('Y'),
                    'month' => $event->getStart()->format('F'),
                    'events' => [],
                ];
            }
            $structuredList[$key]['events'][] = $event;
        }

        return $structuredList;
    }

    public function extentRecurringEvents(): void
    {
        $events = $this->repo->findAllRecurring();
        foreach ($events as $event) {
            $this->fillRecurringEvents($event);
        }
    }

    private function fillRecurringEvents(Event $event): void
    {
        if ($event->getRecurringRule() === null) {
            return;
        }

        $skipFirst = true;
        $today = new DateTime();
        $recurringRule = $event->getRecurringRule();
        $ruleInterval = (EventIntervals::BiMonthly === $recurringRule) ? 2 : 1;
        $ruleFrequency = match ($recurringRule) {
            EventIntervals::Daily => RRule::DAILY,
            EventIntervals::Weekly, EventIntervals::BiMonthly => RRule::WEEKLY,
            EventIntervals::Monthly => RRule::MONTHLY,
            EventIntervals::Yearly => RRule::YEARLY,
            default => throw new RuntimeException('Unknown EventIntervals'),
        };

        $rrule = new RRule([
            'freq' => $ruleFrequency,
            'interval' => $ruleInterval,
            'dtstart' => $this->getLastRecurringEventDate($event),
            'until' => (match ($recurringRule) {
                EventIntervals::Daily => $today->modify('+2 weeks'),
                EventIntervals::Weekly, $today->modify('+3 weeks'),
                EventIntervals::BiMonthly, $today->modify('+5 weeks'),
                EventIntervals::Monthly => $today->modify('+3 months'),
                EventIntervals::Yearly => $today->modify('+3 years'),
                default => throw new RuntimeException('Unknown EventIntervals'),
            })->format('Y-m-d')
        ]);

        foreach ($rrule as $occurrence) {
            if ($skipFirst) {
                $skipFirst = false;
                continue;
            }
            $recurringEvent = new Event();
            $recurringEvent->setUser($event->getUser());
            $recurringEvent->setLocation($event->getLocation());
            $recurringEvent->setInitial(false);
            $recurringEvent->setStart($this->updateDate($event->getStart(), $occurrence));
            $recurringEvent->setStop($this->updateDate($event->getStop(), $occurrence));
            $recurringEvent->setRecurringOf($event->getId());
            $recurringEvent->setRecurringRule(null);
            $recurringEvent->setName($event->getName()); // TODO: set to null
            $recurringEvent->setDescription($event->getDescription()); // TODO: set to null  // TODO: add a recurringContent Pointer to the last changed item and update all following
            $recurringEvent->setDescription($event->getDescription()); // TODO: set to null
            $recurringEvent->setCreatedAt(new DateTimeImmutable());
            $recurringEvent->setHost($event->getHost());

            $this->em->persist($recurringEvent);
            $this->em->flush();
        }
    }

    private function updateDate(DateTimeInterface $target, DateTime $occurrence): DateTimeInterface
    {
        $newDate = clone $target;
        $newDate->setDate(
            year: (int)$occurrence->format('Y'),
            month: (int)$occurrence->format('m'),
            day: (int)$occurrence->format('d'),
        );

        return $newDate;
    }

    private function getLastRecurringEventDate(Event $event): string
    {
        $lastRecurringEvent = $this->repo->findOneBy(['recurringOf' => $event->getId()], ['start' => 'DESC']);
        if ($lastRecurringEvent === null) {
            $lastRecurringEvent = $event;
        }

        return $lastRecurringEvent->getStart()->format('Y-m-d');
    }
}

<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Event;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getEventData() as $eventData) {
            [$initial, $dateStart, $dateStop, $description, $recurringOf, $recurringRule] = $eventData;
            $singleEvent = new Event();
            $singleEvent->setInitial($initial);
            $singleEvent->setStart($this->setDateType($dateStart));
            $singleEvent->setStop($this->setDateType($dateStop));
            $singleEvent->setDescription($description);
            $singleEvent->setRecurringOf($recurringOf);
            $singleEvent->setRecurringRule($recurringRule);
            $manager->persist($singleEvent);
        }

        $manager->flush();
    }

    private function getEventData(): array
    {
        return [
            [true, '2002-02-19', null, 'First Event', 0, null],
            [true, '2020-01-01', null, 'Dinner Event', 0, null],
        ];
    }

    private function setDateType(?string $text): ?DateTime
    {
        if (empty($text)) {
            return null;
        }

        return new DateTime($text);
    }
}

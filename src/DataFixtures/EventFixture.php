<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Event;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EventFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as $data) {
            [$initial, $start, $stop, $desc, $recOf, $recRules, $location] = $data;
            $event = new Event();
            $event->setInitial($initial);
            $event->setStart($this->setDateType($start));
            $event->setStop($this->setDateType($stop));
            $event->setDescription($desc);
            $event->setRecurringOf($recOf);
            $event->setRecurringRule($recRules);
            $event->setUser($this->getReference('user_' . md5('admin@beijingcode.org')));
            $event->setLocation($this->getReference('location_' . md5($location)));

            $manager->persist($event);
            $manager->flush();
        }
    }

    public function getDependencies()
    {
        return [
            UserFixture::class,
            LocationFixture::class,
        ];
    }

    private function getData(): array
    {
        return [
            [true, '2002-02-19', null, 'First Event', 0, null, 'St. Oberholz', ],
            [true, '2020-01-01', null, 'Dinner Event', 0, null, 'Grand Tang', ],
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

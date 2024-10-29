<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Event;
use DateTime;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;

class EventFixture extends Fixture implements DependentFixtureInterface
{
    private const IS_INITIAL = true;
    private const NOT_INITIAL = false;
    private const NO_RECURRING_OF = 0;
    private const NO_RECURRING_RULE = null;

    public function __construct(private readonly Filesystem $fs)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as $data) {
            [$initial, $start, $stop, $name, $desc, $recOf, $recRules, $location, $hosts] = $data;
            $event = new Event();
            $event->setInitial($initial);
            $event->setStart($this->setDateType($start));
            $event->setStop($this->setDateType($stop));
            $event->setName($name);
            $event->setDescription($desc);
            $event->setRecurringOf($recOf);
            $event->setRecurringRule($recRules);
            $event->setUser($this->getReference('user_' . md5('import')));
            $event->setLocation($this->getReference('location_' . md5((string)$location)));
            $event->setCreatedAt(new DateTimeImmutable());
            foreach ($hosts as $host) {
                $event->addHost($this->getReference('host_' . md5((string)$host)));
            }

            $manager->persist($event);
            $manager->flush();
        }
    }

    public function getDependencies()
    {
        return [
            UserFixture::class,
            LocationFixture::class,
            HostFixture::class,
        ];
    }

    private function getData(): array
    {
        return [
            [
                self::IS_INITIAL,
                '2020-09-03 19:00',
                '2020-09-03 20:00',
                'Outdoor Meetup at Himmelbeet',
                $this->getBlob('Himmelbeet'),
                self::NO_RECURRING_OF,
                self::NO_RECURRING_RULE,
                'St. Oberholz',
                ['雪地', '易木']
            ],
        ];
    }

    private function setDateType(?string $text): ?DateTime
    {
        if ($text === null || $text === '' || $text === '0') {
            return null;
        }

        return new DateTime($text);
    }

    private function getBlob(string $string): string
    {
        return $this->fs->readFile(__DIR__ . "/blobs/Event_$string.txt");
    }
}

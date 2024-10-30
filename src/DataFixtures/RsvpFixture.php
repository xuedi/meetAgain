<?php

namespace App\DataFixtures;

use App\Entity\Rsvp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RsvpFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as [$user, $event]) {
            $rsvp = new Rsvp();
            $rsvp->addUser($this->getReference('user_' . md5((string)$user)));
            $rsvp->setEvent($this->getReference('event_' . md5((string)$event)));

            $manager->persist($rsvp);
            $manager->flush();;
        }
    }

    public function getDependencies()
    {
        return [
            UserFixture::class,
            EventFixture::class,
        ];
    }

    private function getData(): array
    {
        $eventA = 'Chinese 中文 Chinesisch/German 德语 Deutsch Meetup! (Every 2 Weeks!)';
        $eventB = '下馆子！Let’s go eat!';
        $eventC = 'Let\'s meet up and talk Chinese!';
        $eventD = 'Spicy Chinese dinner at a Sichuan restaurant';
        $eventE = '中秋节 - Mid Autumn festival';
        $eventF = 'Outdoor Meetup at Himmelbeet';
        return [

            // 'Chinese 中文 Chinesisch/German 德语 Deutsch Meetup! (Every 2 Weeks!)'
            ['xuedi', $eventA],
            ['yimu', $eventA],
            ['xiaolong', $eventA],
            ['user_a', $eventA],
            ['user_b', $eventA],
            ['user_c', $eventA],

            // '下馆子！Let’s go eat!'
            ['xuedi', $eventB],
            ['yimu', $eventB],
            ['xiaolong', $eventB],

            // 'Let\'s meet up and talk Chinese!'
            ['xuedi', $eventC],
            ['yimu', $eventC],
            ['xiaolong', $eventC],

            // 'Spicy Chinese dinner at a Sichuan restaurant'
            ['xuedi', $eventD],
            ['yimu', $eventD],
            ['user_c', $eventD],

            // '中秋节 - Mid Autumn festival'
            ['xuedi', $eventE],
            ['yimu', $eventE],
            ['user_a', $eventE],

            // 'Outdoor Meetup at Himmelbeet'
            ['xuedi', $eventF],
            ['user_a', $eventF],
        ];
    }
}

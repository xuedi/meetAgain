<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Location;
use DateTime;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LocationFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as [$name, $street, $city, $postcode, $description]) {
            $location = new Location();
            $location->setName($name);
            $location->setStreet($street);
            $location->setCity($city);
            $location->setPostcode($postcode);
            $location->setDescription($description);
            $location->setUser($this->getReference('user_' . md5('admin@beijingcode.org')));
            $location->setCreatedAt(new DateTimeImmutable());

            $manager->persist($location);
            $manager->flush();

            $this->addReference('location_' . md5((string) $name), $location);
        }
    }

    public function getDependencies()
    {
        return [
            UserFixture::class,
        ];
    }

    private function getData(): array
    {
        return [
            ['Garten der Welt', 'Eisenacher Str. 99', 'Berlin', '12685', 'BUS 195 stops right next to the entrance where we will meet. If you miss our meeting time, call me on (redacted) and I\'ll try to guide you to us.'],
            ['St. Oberholz', 'Rosenthaler Straße 72', 'Berlin', '10119', ''],
            ['Grand Tang', 'Pestalozzistr. 37', 'Berlin', '10627', ''],
            ['Lao Xiang', 'Wichert Str 43', 'Berlin', '10439', ''],
            ['Volksbar', 'Rosa-Luxemburg-Straße 39', 'Berlin', '10178', ''],
        ];
    }
}

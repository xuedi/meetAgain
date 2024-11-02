<?php

namespace App\DataFixtures;

use App\Entity\Config;
use App\Entity\ConfigType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ConfigFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as [$name, $value, $type]) {
            $user = new Config();
            $user->setName($name);
            $user->setValue($value);
            $user->setType($type);

            $manager->persist($user);
        }
        $manager->flush();
    }

    private function getData(): array
    {
        return [
            ['pageUrl', 'https://www.dragon-descendants.de', ConfigType::String],
            ['recurringTargetMonths', '60', ConfigType::Integer],
            ['isOpenRegistration', 'true', ConfigType::Boolean],
            ['isOffline', 'false', ConfigType::Boolean],
        ];
    }
}

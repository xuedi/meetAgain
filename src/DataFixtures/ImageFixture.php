<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Image;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ImageFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $image = new Image();
        $image->setHash('d9f6449ca222391003844e22d8c9cb1ade994875');
        $image->setExtension('png');
        $image->setMimeType('image/png');
        $image->setSize(11787);
        $image->setAlt('Default for unset images');

        $manager->persist($image);
        $this->addReference('image_default_16x9', $image);
        $manager->flush();
    }
}

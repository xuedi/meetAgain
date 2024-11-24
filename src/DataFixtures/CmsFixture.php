<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Cms;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CmsFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as [$slug]) {
            $cms = new Cms();
            $cms->setSlug($slug);
            $cms->setCreatedAt(new DateTimeImmutable());
            $cms->setCreatedBy($this->getReference('user_' . md5('import')));
            $cms->setPublished(true);

            $manager->persist($cms);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }

    private function getData(): array
    {
        return [
            ['imprint'],
            ['privacy'],
        ];
    }
}

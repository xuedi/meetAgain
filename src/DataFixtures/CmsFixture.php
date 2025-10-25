<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Cms;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CmsFixture extends AbstractFixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const string INDEX = 'index';
    public const string PRIVACY = 'privacy';
    public const string ABOUT = 'about';
    public const string IMPRINT = 'imprint';

    public function load(ObjectManager $manager): void
    {
        $this->start();
        foreach ($this->getData() as [$slug]) {
            $cms = new Cms();
            $cms->setSlug($slug);
            $cms->setCreatedAt(new DateTimeImmutable());
            $cms->setCreatedBy($this->getRefUser(UserFixture::IMPORT));
            $cms->setPublished(true);

            $manager->persist($cms);
            $this->addRefCms($slug, $cms);
        }
        $manager->flush();
        $this->stop();
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['base'];
    }

    private function getData(): array
    {
        return [
            [self::INDEX],
            [self::PRIVACY],
            [self::ABOUT],
            [self::IMPRINT],
        ];
    }
}

<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Image;
use App\Service\UploadService;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ImageFixture extends Fixture implements DependentFixtureInterface
{

    public function __construct(
        private readonly UploadService $imageService,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $defaultImage = new Image();
        $defaultImage->setHash('b184cd5280c69dad635c047e4f98d9cb47ba9613');
        $defaultImage->setExtension('png');
        $defaultImage->setMimeType('image/png');
        $defaultImage->setSize(0);
        $defaultImage->setAlt('Default image for Events');
        $defaultImage->setCreatedAt(new DateTimeImmutable());
        $defaultImage->setUploader($this->getReference('user_' . md5('import')));

        $manager->persist($defaultImage);

        $regularMeetup = new Image();
        $regularMeetup->setHash('71a185614cb3c6315672b3a450b768a6f5ef4d87');
        $regularMeetup->setExtension('jpg');
        $regularMeetup->setMimeType('image/jpg');
        $regularMeetup->setSize(0);
        $regularMeetup->setAlt('Regular meetup picture');
        $regularMeetup->setCreatedAt(new DateTimeImmutable());
        $regularMeetup->setUploader($this->getReference('user_' . md5('import')));

        $manager->persist($regularMeetup);

        $manager->flush();
        $this->imageService->createThumbnails($defaultImage, [[400, 400], [600, 400]]);
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}

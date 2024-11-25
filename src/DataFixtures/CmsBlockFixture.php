<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\CmsBlock;
use App\Entity\CmsBlockTypes;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;

class CmsBlockFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly Filesystem $fs)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as [$page, $lang, $type, $json]) {
            $block = new CmsBlock();
            $block->setPage($this->getReference('cms_' . md5((string)$page)));
            $block->setLanguage($lang);
            $block->setType($type);
            $block->setJson($json);

            $manager->persist($block);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CmsFixture::class,
            UserFixture::class,
        ];
    }

    private function getData(): array
    {
        return [
            ['imprint', 'en', CmsBlockTypes::Headline, ['title' => 'Imprint']],
            ['imprint', 'en', CmsBlockTypes::Text, ['title' => '1. Paragraph', 'content' => 'Some text p1']],
            ['imprint', 'en', CmsBlockTypes::Text, ['title' => '2. Paragraph', 'content' => 'Some text p2']],
            ['imprint', 'de', CmsBlockTypes::Headline, ['title' => 'Impressum']],
            ['imprint', 'de', CmsBlockTypes::Text, ['title' => '1. Paragraf', 'content' => 'Etwas text p1']],
            ['imprint', 'cn', CmsBlockTypes::Headline, ['title' => '版本说明']],
            ['imprint', 'cn', CmsBlockTypes::Text, ['title' => '第 1 段', 'content' => '第一段的一些文字']],
            ['about', 'en', CmsBlockTypes::Headline, ['title' => 'About']],
            ['about', 'en', CmsBlockTypes::Text, ['content' => $this->getBlob('about_en')]],
            ['about', 'de', CmsBlockTypes::Headline, ['title' => 'Über Uns']],
            ['about', 'de', CmsBlockTypes::Text, ['content' => $this->getBlob('about_de')]],
            ['about', 'cn', CmsBlockTypes::Headline, ['title' => '关于我们']],
            ['about', 'cn', CmsBlockTypes::Text, ['content' => $this->getBlob('about_cn')]],
        ];
    }

    private function getBlob(string $string): string
    {
        return $this->fs->readFile(__DIR__ . "/blobs/$string.txt");
    }
}

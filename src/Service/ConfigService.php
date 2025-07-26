<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\ImageType;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Address;

readonly class ConfigService
{
    public function __construct(private ConfigRepository $repo, private EntityManagerInterface $em)
    {
    }

    public function getThumbnailSizes(ImageType $type): array
    {
        return match ($type) {
            ImageType::ProfilePicture => [[400, 400], [80, 80], [50, 50]],
            ImageType::EventTeaser => [[1024, 768], [600, 400], [210, 140]], // included EventUpload
            ImageType::EventUpload => [[1024, 768], [210, 140]],
            ImageType::CmsBlock => [[432, 432], [80, 80]],
        };
    }

    public function getThumbnailSizeList(): array
    {
        return [
            '1024x768' => 0, // gallery image bit
            '600x400' => 0,  // event preview image
            '432x432' => 0,  // cmsBlock image
            '400x400' => 0,  // profile big
            '210x140' => 0,  // gallery image preview
            '80x80' => 0,    // ?
            '50x50' => 0,    // ?
        ];
    }

    public function isValidThumbnailSize(ImageType $type, int $checkWidth, int $checkHeight): bool
    {
        foreach ($this->getThumbnailSizes($type) as list($width, $height)) {
            if ($checkWidth == $width && $checkHeight == $height) {
                return true;
            }
        }

        return false;
    }

    public function getHost(): string
    {
        return $_ENV['APP_HOST'] ?? 'http://localhost';
    }

    public function getUrl(): string
    {
        return $this->getString('website_url', 'localhost');
    }

    public function getMailerAddress(): Address
    {
        return new Address(
            $this->getString('email_sender_mail', 'sender@email.com'),
            $this->getString('email_sender_name', 'email sender'),
        );
    }

    public function isShowFrontpage(): bool
    {
        return $this->getBoolean('show_frontpage', false);
    }

    public function saveForm(array $formData): void
    {
        $this->setString('website_url', $formData['url']);
        $this->setString('email_sender_name', $formData['senderName']);
        $this->setString('email_sender_mail', $formData['senderEmail']);
    }

    private function getBoolean(string $name, bool $default = false): bool
    {
        $setting = $this->repo->findOneBy(['name' => $name]);
        if ($setting === null) {
            return $default;
        }

        return $setting->getValue() === 'true';
    }

    private function getString(string $name, string $default): string
    {
        $setting = $this->repo->findOneBy(['name' => $name]);
        if ($setting === null) {
            return $default;
        }

        return $setting->getValue();
    }

    private function setString(string $name, string $value): void
    {
        $setting = $this->repo->findOneBy(['name' => $name]);
        if ($setting === null) {
            return;
        }

        $setting->setValue($value);

        $this->em->persist($setting);
        $this->em->flush();
    }
}

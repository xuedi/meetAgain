<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Image;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;

readonly class UploadService
{
    public function __construct(
        private ImageRepository $repo,
        private Filesystem $filesystem,
        private KernelInterface $appKernel,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function upload(FormInterface $form, string $fieldName): ?Image
    {
        // ensure form has expected type
        $imageData = $form->get($fieldName)->getData();
        if (!$imageData instanceof UploadedFile) {
            return null;
        }

        // load or create
        $hash = sha1($imageData->getContent());
        $image = $this->repo->findOneBy(['hash' => $hash]);
        if ($image) {
            return $image;
        }

        $image = new Image();
        $image->setHash($hash);
        $image->setMimeType($imageData->getMimeType());
        $image->setExtension($imageData->guessExtension());
        $image->setSize($imageData->getSize() ?? 0);

        $path = $this->appKernel->getProjectDir() . '/assets/images/';
        $file = $path . $image->getHash() . '.' . $image->getExtension();
        $this->filesystem->copy($imageData->getRealPath(), $file);

        $this->entityManager->persist($image);

        return $image;
    }
}

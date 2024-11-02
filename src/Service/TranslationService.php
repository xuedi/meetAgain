<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Translation;
use App\Repository\TranslationRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TranslationService
{
    public function __construct(
        private readonly TranslationRepository $translationRepo,
        private readonly UserRepository $userRepo,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getMatrix(): array
    {
        $structuredList = [];

        $translations = $this->translationRepo->findAll();
        foreach ($translations as $translation) {
            $id = $translation->getId();
            $lang = $translation->getLanguage();
            $placeholder = $translation->getPlaceholder();
            $translation = $translation->getTranslation() ?? '';
            $structuredList[$placeholder][$lang] = [
                'id' => $id,
                'value' => $translation,
            ];
        }

        return $structuredList;
    }

    public function saveMatrix(Request $request): void
    {
        $dataBase = $this->translationRepo->buildKeyValueList();
        $params = $request->getPayload();

        foreach ($params as $key => $newTranslation) {
            if (array_key_exists($key, $dataBase) && $dataBase[$key] !== $newTranslation) {
                $translationEntity = $this->translationRepo->findOneBy(['id' => $key]);
                if ($translationEntity === null || empty($newTranslation)) {
                    continue;
                }

                $translationEntity->setTranslation($newTranslation);
                $translationEntity->setCreatedAt(new DateTimeImmutable());
                $this->entityManager->persist($translationEntity);
            }
        }

        $this->entityManager->flush();
    }

    public function extract(): void
    {
        $this->extractTranslationsFromFilesystem();

        $path = __DIR__ . '/../../translations/';
        $dataBase = $this->translationRepo->getUniqueList();
        $importUser = $this->userRepo->findOneBy(['email' => 'system@beijingcode.org']);
        dump($dataBase);

        $finder = new Finder();
        $finder->files()->in($path)->depth(0)->name(['messages*.php']);
        foreach ($finder as $file) {
            [$name, $lang, $ext] = explode('.', $file->getFilename());
            foreach (include($file->getPathname()) as $placeholder => $translationMessage) {
                if (!in_array($placeholder, $dataBase[$lang])) {
                    $translation = new Translation();
                    $translation->setLanguage($lang);
                    $translation->setPlaceholder($placeholder);
                    $translation->setCreatedAt(new DateTimeImmutable());
                    $translation->setUser($importUser);
                    $this->entityManager->persist($translation);
                }
            }
        }
        $this->entityManager->flush();
    }

    public function publish(): void
    {
        //
    }

    // TODO: trigger from code
    private function extractTranslationsFromFilesystem(): void
    {
        $process = new Process(['just', 'translationsExtract']);
        $process->setWorkingDirectory(realpath(__DIR__ . "/../../"));
        $process->enableOutput();
        $process->start();
        $process->wait();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}

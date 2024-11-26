<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Translation;
use App\Repository\TranslationRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

readonly class TranslationService
{
    public function __construct(
        private TranslationRepository $translationRepo,
        private UserRepository $userRepo,
        private EntityManagerInterface $entityManager,
        private Filesystem $fs,
        private KernelInterface $appKernel,
        private ParameterBagInterface $appParams,
        private JustService $just,
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
        ksort($structuredList, SORT_NATURAL);

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

    public function extract(): array
    {
        $numberTranslationCount = 0;
        $newTranslations = 0;
        $extractionTime = $this->just->command('translationsExtract');

        $path = $this->appKernel->getProjectDir() . '/translations/';
        $dataBase = $this->translationRepo->getUniqueList();
        $importUser = $this->userRepo->findOneBy(['email' => 'system@beijingcode.org']);

        $finder = new Finder();
        $finder->files()->in($path)->depth(0)->name(['messages*.php']);
        foreach ($finder as $file) {
            [$name, $lang, $ext] = explode('.', $file->getFilename());
            $translations = $this->removeDuplicates(include($file->getPathname()));
            foreach ($translations as $placeholder => $translationMessage) {
                if (!in_array($placeholder, $dataBase[$lang], true)) {
                    $translation = new Translation();
                    $translation->setLanguage($lang);
                    $translation->setPlaceholder($placeholder);
                    $translation->setCreatedAt(new DateTimeImmutable());
                    $translation->setUser($importUser);
                    $this->entityManager->persist($translation);
                    $newTranslations++;
                }
                $numberTranslationCount++;
            }
        }
        $this->entityManager->flush();
        $this->publish();

        return [
            'count' => $numberTranslationCount,
            'new' => $newTranslations,
            'extractionTime' => $extractionTime,
        ];
    }

    public function publish(): array
    {
        $cleanedUp = 0;
        $published = 0;
        $locales = $this->appParams->get('kernel.enabled_locales');

        // clean up old translation files
        $path = $this->appKernel->getProjectDir() . '/translations/';
        $finder = new Finder();
        $finder->files()->in($path)->depth(0)->name(['*.php']);
        foreach ($finder as $file) {
            $this->fs->remove($file->getPathname());
            $cleanedUp++;
        }

        // create new translation files
        foreach ($locales as $locale) {
            $file = $path . 'messages.' . $locale . '.php';
            $this->fs->appendToFile($file, '<?php return array (');
            $translations = $this->translationRepo->findBy(['language' => $locale]);
            foreach ($translations as $translation) {
                $this->fs->appendToFile($file, sprintf(
                    "'%s' => '%s',",
                    $translation->getPlaceholder(),
                    $translation->getTranslation(),
                ));
                $published++;
            }
            $this->fs->appendToFile($file, ');');
        }
        $this->just->command('clearCache');

        return [
            'cleanedUp' => $cleanedUp,
            'published' => $published,
            'languages' => $locales,
        ];
    }

    private function removeDuplicates(array $translations): array
    {
        $cleanedList = [];
        foreach ($translations as $key => $translation) {
            if(!isset($cleanedList[strtolower($key)])) {
                $cleanedList[strtolower($key)] = $translation;
            }
        }
        return $cleanedList;
    }
}

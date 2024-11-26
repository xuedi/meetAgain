<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\Host;
use App\Entity\Image;
use App\Entity\Location;
use App\Entity\User;
use App\Form\EventType;
use App\Form\HostType;
use App\Form\LocationType;
use App\Form\UserType;
use App\Repository\CmsRepository;
use App\Repository\ConfigRepository;
use App\Repository\EventRepository;
use App\Repository\HostRepository;
use App\Repository\LocationRepository;
use App\Repository\UserRepository;
use App\Service\EventService;
use App\Service\TranslationService;
use App\Service\UploadService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages as AssertManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/admin/translations')]
class AdminTranslationController extends AbstractController
{
    #[Route('/', name: 'app_admin_translation')]
    public function translationsIndex(TranslationService $translationService): Response
    {
        return $this->render('admin/translations/list.html.twig', [
            'translationMatrix' => $translationService->getMatrix(),
        ]);
    }

    #[Route('/save', name: 'app_admin_translation_save')]
    public function translationsSave(TranslationService $translationService, Request $request): Response
    {
        // todo check token
        $translationService->saveMatrix($request);
        // flash message

        return $this->redirectToRoute('app_admin_translation');
    }

    #[Route('/extract', name: 'app_admin_translation_extract')]
    public function translationsExtract(TranslationService $translationService, LoggerInterface $logger): Response
    {
        try {
            return $this->render('admin/translations/extract.html.twig', [
                'result' => $translationService->extract(),
            ]);
        } catch (Throwable $exception) {
            $logger->info('translationsPublish(): I got an error: ' . $exception->getMessage());
            return $this->redirectToRoute('app_admin_translation');
        }
    }

    #[Route('/publish', name: 'app_admin_translation_publish')]
    public function translationsPublish(TranslationService $translationService, LoggerInterface $logger): Response
    {
        try {
            return $this->render('admin/translations/publish.html.twig', [
                'result' => $translationService->publish(),
            ]);
        } catch (Throwable $exception) {
            $logger->info('translationsPublish(): I got an error: ' . $exception->getMessage());
            return $this->redirectToRoute('app_admin_translation');
        }
    }
}

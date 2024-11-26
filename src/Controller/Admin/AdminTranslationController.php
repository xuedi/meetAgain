<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
    public function translationsExtract(TranslationService $translationService): Response
    {
        return $this->render('admin/translations/extract.html.twig', [
            'result' => $translationService->extract(),
        ]);
    }

    #[Route('/publish', name: 'app_admin_translation_publish')]
    public function translationsPublish(TranslationService $translationService): Response
    {
        return $this->render('admin/translations/publish.html.twig', [
            'result' => $translationService->publish(),
        ]);
    }
}

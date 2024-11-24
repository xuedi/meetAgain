<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Cms;
use App\Form\CmsType;
use App\Repository\CmsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/cms')]
class AdminCmsController extends AbstractController
{
    #[Route('/', name: 'app_admin_cms')]
    public function cmsList(CmsRepository $repo): Response
    {
        return $this->render('admin/cms/list.html.twig', [
            'cms' => $repo->findAll(),
        ]);
    }

    #[Route('/{id}/{locale}', name: 'app_admin_cms_edit', requirements: ['locale' => 'en|de|cn'], methods: ['GET', 'POST'])]
    public function cmsEdit(Request $request, Cms $cms, EntityManagerInterface $entityManager, string $locale = 'en'): Response
    {
        $form = $this->createForm(CmsType::class, $cms);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_cms');
        }

        return $this->render('admin/cms/edit.html.twig', [
            'editLocale' => $locale,
            'form' => $form,
            'cms' => $cms,
            'blocks' => $cms->getLanguageFilteredBlockJsonList($locale),
        ]);
    }
}

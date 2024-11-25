<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\BlockType\Headline;
use App\Entity\BlockType\Image;
use App\Entity\BlockType\Paragraph;
use App\Entity\BlockType\Text;
use App\Entity\Cms;
use App\Entity\CmsBlock;
use App\Form\CmsType;
use App\Repository\CmsRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/cms')]
class AdminCmsController extends AbstractController
{
    #[Route('/', name: 'app_admin_cms')]
    public function cmsList(CmsRepository $repo): Response
    {
        $newForm = $this->createForm(CmsType::class, null, [
            'action' => $this->generateUrl('app_admin_cms_add'),
        ]);

        return $this->render('admin/cms/list.html.twig', [
            'form' => $newForm,
            'cms' => $repo->findAll(),
        ]);
    }

    #[Route('/edit/{id}/{locale}', name: 'app_admin_cms_edit', requirements: ['locale' => 'en|de|cn'], methods: ['GET', 'POST'])]
    public function cmsEdit(Request $request, Cms $cms, EntityManagerInterface $em, string $locale = 'en'): Response
    {
        $form = $this->createForm(CmsType::class, $cms);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_admin_cms');
        }

        return $this->render('admin/cms/edit.html.twig', [
            'newBlocks' => [Headline::getType()->name, Paragraph::getType()->name, Text::getType()->name, Image::getType()->name],
            'editLocale' => $locale,
            'form' => $form,
            'cms' => $cms,
            'blocks' => $cms->getLanguageFilteredBlockJsonList($locale),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_cms_delete', methods: ['GET'])]
    public function cmsDelete(CmsRepository $repo, EntityManagerInterface $em, int $id): Response
    {
        $cmsPage = $repo->find($id);
        if ($cmsPage !== null) {
            $em->remove($cmsPage);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_cms');
    }

    #[Route('/add', name: 'app_admin_cms_add', methods: ['POST'])]
    public function cmsAdd(Request $request, EntityManagerInterface $em): Response
    {
        $newPage = new Cms();
        $newPage->setSlug($request->get('cms')['slug']);
        $newPage->setPublished(false);
        $newPage->setCreatedBy($this->getUser());
        $newPage->setCreatedAt(new DateTimeImmutable());

        $em->persist($newPage);
        $em->flush();

        return $this->redirectToRoute('app_admin_cms_edit', [
            'id' => $newPage->getId(),
            'locale' => $request->getLocale(),
        ]);
    }

    #[Route('/block/{id}/add', name: 'app_admin_cms_add_block', methods: ['POST'])]
    public function cmsAddBlock(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $cmsRepo = $em->getRepository(Cms::class);
        $cmsPage = $cmsRepo->find($id);
        if ($cmsPage === null) {
            throw new RuntimeException('Could not find valid page');
        }

        $payload = $request->getPayload()->all();
        $locale = $request->get('editLocale');
        $blockType = $request->get('blockType');

        $blockObject = match($blockType) {
            Headline::getType()->name => Headline::fromJson($payload),
            Paragraph::getType()->name => Paragraph::fromJson($payload),
            Text::getType()->name => Text::fromJson($payload),
            Image::getType()->name => Image::fromJson($payload),
        };

        $cmsBlock = new CmsBlock();
        $cmsBlock->setLanguage($locale);
        $cmsBlock->setType($blockObject->getType());
        $cmsBlock->setJson($blockObject->toArray());

        $cmsPage->addBlock($cmsBlock);
        $em->persist($cmsBlock);
        $em->flush();

        return $this->redirectToRoute('app_admin_cms_edit', [
            'id' => $id,
            'locale' => $locale,
        ]);
    }

    private function createFormView(string $class, int $id): FormView
    {

        return $this->createForm($class, null, [
            'action' => $this->generateUrl('app_admin_cms_add_block', ['id' => $id]),
        ])->createView();
    }
}

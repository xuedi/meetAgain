<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\BlockType\BlockType;
use App\Entity\BlockType\Headline;
use App\Entity\BlockType\Hero;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

//TODO: move lots of logic to service

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

    #[Route('/{id}/edit/{locale}/{blockId}', name: 'app_admin_cms_edit', requirements: ['locale' => 'en|de|cn'], methods: ['GET', 'POST'])]
    public function cmsEdit(Request $request, Cms $cms, EntityManagerInterface $em, string $locale = 'en', int $blockId = null): Response
    {
        $form = $this->createForm(CmsType::class, $cms);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_admin_cms');
        }

        $blocks = $em->getRepository(CmsBlock::class)->findBy([
            'page' => $cms->getId(),
            'language' => $locale,
        ], ['priority' => 'ASC']);

        $newBlocks = [
            Headline::getType()->name,
            Paragraph::getType()->name,
            Text::getType()->name,
            Image::getType()->name,
            Hero::getType()->name,
        ];

        return $this->render('admin/cms/edit.html.twig', [
            'newBlocks' => $newBlocks,
            'editLocale' => $locale,
            'editBlock' => $blockId,
            'blocks' => $blocks,
            'form' => $form,
            'cms' => $cms,
        ]);
    }

    #[Route('/delete', name: 'app_admin_cms_delete', methods: ['GET'])]
    public function cmsDelete(Request $request, CmsRepository $repo, EntityManagerInterface $em): Response
    {
        $id = $request->query->get('id');
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
    public function cmsBlockAdd(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $cmsRepo = $em->getRepository(Cms::class);
        $cmsPage = $cmsRepo->find($id);
        if ($cmsPage === null) {
            throw new RuntimeException('Could not find valid page');
        }

        $payload = $request->getPayload()->all();
        $locale = $request->get('editLocale');
        $blockType = $request->get('blockType');
        $blockObject = $this->getBlock($blockType, $payload);

        $cmsBlock = new CmsBlock();
        $cmsBlock->setLanguage($locale);
        $cmsBlock->setPriority($em->getRepository(CmsBlock::class)->getMaxPriority() + 1);
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

    #[Route('/block/down', name: 'app_admin_cms_edit_block_down', methods: ['GET'])]
    public function cmsBlockMoveDown(Request $request, EntityManagerInterface $em): Response
    {
        $pageId = $request->get('id');
        $blockId = $request->get('blockId');
        $locale = $request->get('locale');

        $this->adjustPrio($em, $pageId, $blockId, $locale, 1.5);

        return $this->redirectToRoute('app_admin_cms_edit', [
            'id' => $pageId,
            'locale' => $locale,
        ]);
    }

    #[Route('/block/up', name: 'app_admin_cms_edit_block_up', methods: ['GET'])]
    public function cmsBlockMoveUp(Request $request, EntityManagerInterface $em): Response
    {
        $pageId = $request->get('id');
        $blockId = $request->get('blockId');
        $locale = $request->get('locale');

        $this->adjustPrio($em, $pageId, $blockId, $locale, -1.5);

        return $this->redirectToRoute('app_admin_cms_edit', [
            'id' => $pageId,
            'locale' => $locale,
        ]);
    }

    #[Route('/block/save', name: 'app_admin_cms_edit_block_save', methods: ['POST'])]
    public function cmsBlockSave(Request $request, EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(CmsBlock::class);
        $block = $repo->find($request->get('blockId'));
        if ($block) {
            $block->setJson($this->getBlock($request->get('blockType'), $request->getPayload()->all())->toArray());
            $em->persist($block);
            $em->flush();
        } else {
            throw new RuntimeException('Could not load block');
        }

        return $this->redirectToRoute('app_admin_cms_edit', [
            'id' => $request->get('id'),
            'locale' => $request->get('locale'),
        ]);
    }

    #[Route('/block/delete', name: 'app_admin_cms_block_delete', methods: ['GET'])]
    public function cmsBlockDelete(Request $request, EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(CmsBlock::class);
        $block = $repo->find($request->get('blockId'));
        if ($block) {
            $em->remove($block);
            $em->flush();
        } else {
            throw new RuntimeException('Could not load block');
        }

        return $this->redirectToRoute('app_admin_cms_edit', [
            'id' => $request->get('id'),
            'locale' => $request->get('locale'),
        ]);
    }

    private function getBlock(string $blockType, array $payload): BlockType
    {
        return match ($blockType) {
            Headline::getType()->name => Headline::fromJson($payload),
            Paragraph::getType()->name => Paragraph::fromJson($payload),
            Text::getType()->name => Text::fromJson($payload),
            Image::getType()->name => Image::fromJson($payload),
            Hero::getType()->name => Hero::fromJson($payload),
        };
    }

    private function adjustPrio(EntityManagerInterface $em, mixed $pageId, mixed $blockId, mixed $locale, float $value): void
    {
        // update half up or down
        $repo = $em->getRepository(CmsBlock::class);
        $block = $repo->find($blockId);
        if ($block) {
            $block->setPriority($block->getPriority()+$value);
            $em->persist($block);
            $em->flush();
        } else {
            throw new RuntimeException('Could not load block');
        }


        // rebuild order
        $priority = 1;
        $blocks = $em->getRepository(CmsBlock::class)->findBy([
            'page' => $pageId,
            'language' => $locale,
        ], ['priority' => 'ASC']);

        foreach ($blocks as $block) {
            $block->setPriority($priority);
            $priority++;
            $em->persist($block);
        }
        $em->flush();

    }
}

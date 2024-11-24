<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\CmsType;
use App\Entity\User;
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

    #[Route('/{id}', name: 'app_admin_cms_edit', methods: ['GET', 'POST'])]
    public function cmsEdit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        dump('CMS-edit');
        exit;
    }
}

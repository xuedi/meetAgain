<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\ConfigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/config', name: 'app_admin_config')]
    public function configIndex(ConfigRepository $repo): Response
    {
        return $this->render('admin/config.html.twig', [
            'config' => $repo->findAll(),
        ]);
    }
}

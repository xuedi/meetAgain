<?php

namespace App\Controller;

use App\Repository\ConfigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminConfigController extends AbstractController
{
    public function __construct(private readonly ConfigRepository $repo)
    {
    }

    #[Route('/admin/config', name: 'app_admin_config')]
    public function index(): Response
    {
        return $this->render('admin/config.html.twig', [
            'config' => $this->repo->findAll(),
        ]);
    }
}

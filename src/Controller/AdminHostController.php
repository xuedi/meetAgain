<?php

namespace App\Controller;

use App\Repository\HostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminHostController extends AbstractController
{
    public function __construct(private readonly HostRepository $repo)
    {
    }

    #[Route('/admin/hosts', name: 'app_admin_hosts')]
    public function index(): Response
    {
        return $this->render('admin/host.html.twig', [
            'hosts' => $this->repo->findAll(),
        ]);
    }
}

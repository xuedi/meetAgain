<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminUserController extends AbstractController
{
    public function __construct(private readonly UserRepository $repo)
    {
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function index(): Response
    {
        return $this->render('admin/user.html.twig', [
            'users' => $this->repo->findAll(),
        ]);
    }
}

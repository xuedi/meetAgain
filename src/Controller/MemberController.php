<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MemberController extends AbstractController
{
    #[Route('/members', name: 'app_member')]
    public function index(UserRepository $repo): Response
    {
        return $this->render('member/index.html.twig', [
            'members' => $repo->findAll(),
        ]);
    }
}

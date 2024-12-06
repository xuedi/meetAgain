<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\UserStatus;
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
            'members' => $repo->findBy(['status' => UserStatus::Active, 'public' => true], ['createdAt' => 'ASC']),
        ]);
    }

    #[Route('/members/{id}', name: 'app_member_view')]
    public function view(UserRepository $repo): Response
    {
        return $this->render('member/view.html.twig', [
            'members' => $repo->findAll(),
        ]);
    }
}

<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(EventRepository $repo): Response
    {
        return $this->render('index/index.html.twig', [
            'events' => $repo->getUpcomingEvents(),
        ]);
    }
}

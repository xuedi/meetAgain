<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RequestContext;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(EventRepository $repo, RequestContext $request): Response
    {
        return $this->render('index/index.html.twig', [
            'title' => 'drfdfg',
            'events' => $repo->getUpcomingEvents(),
        ]);
    }
}

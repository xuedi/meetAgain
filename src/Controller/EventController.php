<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends AbstractController
{
    public function __construct(
        private EventRepository $repo,
    ) {
    }

    #[Route('/events', name: 'app_events')]
    public function number(): Response
    {
        return $this->render('events/index.html.twig', [
            'events' => $this->repo->findAll(),
        ]);
    }
}

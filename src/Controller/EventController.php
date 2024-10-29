<?php

namespace App\Controller;

use App\Service\EventViewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends AbstractController
{
    public function __construct(private readonly EventViewService $eventViewService)
    {
    }

    #[Route('/events', name: 'app_events')]
    public function index(): Response
    {
        return $this->render('events/index.html.twig', [
            'structuredList' => $this->eventViewService->getList(),
        ]);
    }
}

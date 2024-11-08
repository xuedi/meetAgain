<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\EventRepository;
use App\Service\EventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'app_event')]
    public function index(EventService $eventService, Request $request): Response
    {
        return $this->render('events/index.html.twig', [
            'structuredList' => $eventService->getList($request->get('name'), $request->get('type')),
            'timeFilter' => $request->get('time'),
            'typeFilter' => $request->get('type'),
        ]);
    }

    #[Route('/event/{id}', name: 'app_event_details', requirements: ['id'=>'\d+'])]
    public function details(EventRepository $repo, int $id = null): Response
    {
        return $this->render('events/details.html.twig', [
            'event' => $repo->findOneBy(['id' => $id]),
        ]);
    }
}

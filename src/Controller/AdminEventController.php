<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminEventController extends AbstractController
{
    public function __construct(private readonly EventRepository $repo)
    {
    }

    #[Route('/admin/events', name: 'app_admin_events')]
    public function index(): Response
    {
        return $this->render('admin/event.html.twig', [
            'events' => $this->repo->findBy([], ['start' => 'ASC']),
        ]);
    }
}

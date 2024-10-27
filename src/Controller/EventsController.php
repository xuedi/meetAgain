<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventsController extends AbstractController
{
    #[Route('/events', name: 'app_events')]
    public function number(): Response
    {
        $number = random_int(0, 100);

        return $this->render('events/index.html.twig', [
            'number' => $number,
        ]);
    }
}

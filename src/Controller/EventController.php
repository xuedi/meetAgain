<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Service\EventService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

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

    #[Route('/event/toggleRsvp/{event}/', name: 'app_event_toggle_rsvp')]
    public function toggleRsvp(Event $event, EntityManagerInterface $em): Response
    {
        $event->toggleRsvp($this->getAuthedUser());
        $em->persist($event);
        $em->flush();

        return $this->redirectToRoute('app_event_details', ['id' => $event->getId()]);
    }

    private function getAuthedUser(): User
    { // just to avoid phpstorms nullpointer warning TODO: put into custom abstract
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AuthenticationCredentialsNotFoundException(
                "Should never happen, see: config/packages/security.yaml"
            );
        }

        return $user;
    }
}

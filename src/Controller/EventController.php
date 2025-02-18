<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventFilterRsvp;
use App\Entity\EventFilterSort;
use App\Entity\EventFilterTime;
use App\Entity\EventTypes;
use App\Form\EventFilterType;
use App\Repository\CommentRepository;
use App\Repository\EventRepository;
use App\Service\EventService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'app_event')]
    public function index(EventService $eventService, Request $request): Response
    {
        $form = $this->createForm(EventFilterType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $time = $form->getData()['time'];
            $sort = $form->getData()['sort'];
            $type = $form->getData()['type'];
            $rsvp = $form->getData()['rsvp'];
        } else {
            $time = EventFilterTime::Future;
            $sort = EventFilterSort::OldToNew;
            $type = EventTypes::All;
            $rsvp = EventFilterRsvp::All;
        }

        return $this->render('events/index.html.twig', [
            'structuredList' => $eventService->getFilteredList($time, $sort, $type, $rsvp),
            'filter' => $form,
        ]);
    }

    #[Route('/event/{id}', name: 'app_event_details', requirements: ['id' => '\d+'])]
    public function details(EventRepository $repo, CommentRepository $comments, ?int $id = null): Response
    {
        return $this->render('events/details.html.twig', [
            'comments' => $comments->findBy(['event' => $id]), // TODO: use custom repo with builder so userInfos are not lazy load
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
}

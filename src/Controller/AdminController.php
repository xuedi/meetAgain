<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Host;
use App\Entity\Location;
use App\Entity\User;
use App\Form\EventType;
use App\Form\HostType;
use App\Form\LocationType;
use App\Form\UserType;
use App\Repository\ConfigRepository;
use App\Repository\EventRepository;
use App\Repository\HostRepository;
use App\Repository\LocationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/users', name: 'app_admin_users')]
    public function userIndex(UserRepository $repo): Response
    {
        return $this->render('admin/user/list.html.twig', [
            'users' => $repo->findAll(),
        ]);
    }

    #[Route('/users/{id}', name: 'app_admin_users_edit', methods: ['GET', 'POST'])]
    public function userEdit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
    #[Route('/events', name: 'app_admin_events')]
    public function eventIndex(EventRepository $repo): Response
    {
        return $this->render('admin/event/list.html.twig', [
            'events' => $repo->findBy([], ['start' => 'ASC']),
        ]);
    }

    #[Route('/events/{id}', name: 'app_admin_events_edit', methods: ['GET', 'POST'])]
    public function eventEdit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        //overwrite
        $event->setInitial(true);

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_events');
        }

        return $this->render('admin/event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }
    #[Route('/hosts', name: 'app_admin_hosts')]
    public function hostIndex(HostRepository $repo): Response
    {
        return $this->render('admin/host/list.html.twig', [
            'hosts' => $repo->findAll(),
        ]);
    }

    #[Route('/hosts/{id}', name: 'app_admin_hosts_edit', methods: ['GET', 'POST'])]
    public function hostEdit(Request $request, Host $host, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HostType::class, $host);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_hosts');
        }

        return $this->render('admin/host/edit.html.twig', [
            'host' => $host,
            'form' => $form,
        ]);
    }

    #[Route('/locations', name: 'app_admin_locations')]
    public function locationIndex(LocationRepository $repo): Response
    {
        return $this->render('admin/location/list.html.twig', [
            'locations' => $repo->findAll(),
        ]);
    }

    #[Route('/locations/{id}', name: 'app_admin_locations_edit', methods: ['GET', 'POST'])]
    public function locationEdit(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_hosts');
        }

        return $this->render('admin/location/edit.html.twig', [
            'location' => $location,
            'form' => $form,
        ]);
    }



    #[Route('/config', name: 'app_admin_config')]
    public function configIndex(ConfigRepository $repo): Response
    {
        return $this->render('admin/config.html.twig', [
            'config' => $repo->findAll(),
        ]);
    }
}

<?php declare(strict_types=1);

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
use App\Service\EventService;
use App\Service\TranslationService;
use App\Service\UploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages as AssertManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/users', name: 'app_admin_user')]
    public function userIndex(UserRepository $repo): Response
    {
        return $this->render('admin/user/list.html.twig', [
            'users' => $repo->findAll(),
        ]);
    }

    #[Route('/users/{id}', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function userEdit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_user');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
    #[Route('/events', name: 'app_admin_event')]
    public function eventIndex(EventRepository $repo): Response
    {
        return $this->render('admin/event/list.html.twig', [
            'events' => $repo->findBy([], ['start' => 'ASC']),
        ]);
    }

    #[Route('/events/{id}', name: 'app_admin_event_edit', methods: ['GET', 'POST'])]
    public function eventEdit(
        Request $request,
        Event $event,
        UploadService $uploadService,
        EntityManagerInterface $entityManager,
    ): Response {

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $event->setPreviewImage($uploadService->upload($form, 'image'));
            $event->setInitial(true);
            $event->setUser($this->getUser());

            $entityManager->persist($event);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_event');
        }

        return $this->render('admin/event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }
    #[Route('/hosts', name: 'app_admin_host')]
    public function hostIndex(HostRepository $repo): Response
    {
        return $this->render('admin/host/list.html.twig', [
            'hosts' => $repo->findAll(),
        ]);
    }

    #[Route('/hosts/{id}', name: 'app_admin_host_edit', methods: ['GET', 'POST'])]
    public function hostEdit(Request $request, Host $host, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HostType::class, $host);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_host');
        }

        return $this->render('admin/host/edit.html.twig', [
            'host' => $host,
            'form' => $form,
        ]);
    }

    #[Route('/locations', name: 'app_admin_location')]
    public function locationIndex(LocationRepository $repo): Response
    {
        return $this->render('admin/location/list.html.twig', [
            'locations' => $repo->findAll(),
        ]);
    }

    #[Route('/locations/{id}', name: 'app_admin_location_edit', methods: ['GET', 'POST'])]
    public function locationEdit(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_location');
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

    #[Route('/cms', name: 'app_admin_cms')]
    public function cmsIndex(ConfigRepository $repo): Response
    {
        return $this->render('admin/config.html.twig', [
            'config' => $repo->findAll(),
        ]);
    }

    #[Route('/translations', name: 'app_admin_translation')]
    public function translationsIndex(TranslationService $translationService): Response
    {
        return $this->render('admin/translations/list.html.twig', [
            'translationMatrix' => $translationService->getMatrix(),
        ]);
    }

    #[Route('/translations/save', name: 'app_admin_translation_save')]
    public function translationsSave(TranslationService $translationService, Request $request): Response
    {
        // todo check token
        $translationService->saveMatrix($request);
        // flash message

        return $this->redirectToRoute('app_admin_translation');
    }

    #[Route('/translations/extract', name: 'app_admin_translation_extract')]
    public function translationsExtract(TranslationService $translationService): Response
    {
        return $this->render('admin/translations/extract.html.twig', [
            'result' => $translationService->extract(),
        ]);
    }

    #[Route('/translations/publish', name: 'app_admin_translation_publish')]
    public function translationsPublish(TranslationService $translationService): Response
    {
        return $this->render('admin/translations/publish.html.twig', [
            'result' => $translationService->publish(),
        ]);
    }
}

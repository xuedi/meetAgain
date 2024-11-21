<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\EventRepository;
use App\Service\UploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile')]
    public function index(
        Request $request,
        EventRepository $repo,
        UploadService $uploadService,
        EntityManagerInterface $entityManager,
    ): Response {

        $form = $this->createForm(ProfileType::class, $this->getAuthedUser());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $image = $uploadService->upload($form, 'image', $this->getAuthedUser());
            $user = $this->getAuthedUser();
            $user->setBio($form->get('bio')->getData());
            $user->setLocale($form->get('languages')->getData());
            if ($image) {
                $user->setImage($image);
            }

            $entityManager->persist($user);
            $entityManager->flush();
            if ($image) {
                $uploadService->createThumbnails($image, [[400,400]]);
            }

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $this->getAuthedUser(),
            'upcoming' => $repo->getUpcomingEvents(10),
            'past' => $repo->getPastEvents(20),
            'form' => $form,
        ]);
    }

    #[Route('/toggleRsvp/{event}/', name: 'app_profile_toggle_rsvp')]
    public function toggleRsvp(Event $event, EntityManagerInterface $em): Response
    {
        $event->toggleRsvp($this->getAuthedUser());
        $em->persist($event);
        $em->flush();

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/messages', name: 'app_profile_messages')]
    public function messages(): Response
    {
        return $this->render('profile/messages.html.twig');
    }

    #[Route('/config', name: 'app_profile_config')]
    public function config(): Response
    {
        return $this->render('profile/config.html.twig');
    }

    #[Route('/logout', name: 'app_profile_logout')]
    public function logout(Security $security): Response
    {
        $security->logout(false);

        return $this->forward('App\Controller\IndexController::index');
    }

    private function getAuthedUser(): User
    { // just to avoid phpstorms nullpointer warning
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AuthenticationCredentialsNotFoundException(
                "Should never happen, see: config/packages/security.yaml"
            );
        }

        return $user;
    }
}

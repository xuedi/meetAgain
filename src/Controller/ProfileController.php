<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\LocaleSwitcher;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/events', name: 'app_profile_events')]
    public function events(): Response
    {
        return $this->render('profile/events.html.twig');
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

    #[Route('/language/{locale}', name: 'app_profile_language', requirements: ['locale' => 'en|de|cn'])]
    public function setLanguage(
        Request $request,
        EntityManagerInterface $entityManager,
        string $locale
    ): Response {

        // set session
        $session = $request->getSession();
        $session->set('_locale', $locale);

        // set user
        $user = $this->getUser();
        if ($user !== null) { // only for phpstorm, symfony security is enforcing this
            $user->setLocale($locale);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->forward('App\Controller\IndexController::index');
    }
}

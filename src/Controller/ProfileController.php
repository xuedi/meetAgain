<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/profile/events', name: 'app_profile_events')]
    public function events(): Response
    {
        return $this->render('profile/events.html.twig');
    }

    #[Route('/profile/messages', name: 'app_profile_messages')]
    public function messages(): Response
    {
        return $this->render('profile/messages.html.twig');
    }

    #[Route('/profile/config', name: 'app_profile_config')]
    public function config(): Response
    {
        return $this->render('profile/config.html.twig');
    }
}

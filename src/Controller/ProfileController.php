<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('/logout', name: 'app_profile_logout')]
    public function logout(Security $security): Response
    {
        $security->logout(false);

        return $this->forward('App\Controller\IndexController::index');
    }
}

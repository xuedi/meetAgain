<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(EventRepository $repo): Response
    {
        return $this->render('index/index.html.twig', [
            'title' => 'drfdfg',
            'events' => $repo->getUpcomingEvents(),
        ]);
    }

    #[Route('/language/{locale}', name: 'app_default_language', requirements: ['locale' => 'en|de|cn'])]
    public function setLanguage(Request $request, EntityManagerInterface $entityManager, string $locale): Response {

        // set session
        $session = $request->getSession();
        $session->set('_locale', $locale);

        // set user preferences in DB
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            $user->setLocale($locale);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->forward('App\Controller\IndexController::index'); // TODO: add proper route instead
    }
}

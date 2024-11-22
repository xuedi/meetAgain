<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CmsController extends AbstractController
{
    //TODO: hook into the router via event listener for CMS routing

    #[Route('/', name: 'app_imprint')]
    public function index(EventRepository $repo): Response
    {
        return $this->render('cms/index.html.twig', [
            'title' => 'drfdfg',
            'events' => $repo->getUpcomingEvents(),
        ]);
    }
}

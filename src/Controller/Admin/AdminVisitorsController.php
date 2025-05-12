<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\NotFoundLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminVisitorsController extends AbstractController
{
    #[Route('/admin/visitors/', name: 'app_admin_visitors')]
    public function notFoundVisits(NotFoundLogRepository $repo): Response
    {
        return $this->render('admin/visitors/index.html.twig', [
            'notFound' => $repo->findAll(),
        ]);
    }
}

<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Host;
use App\Form\HostType;
use App\Repository\ActivityRepository;
use App\Repository\HostRepository;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/logs')]
class AdminLogsController extends AbstractController
{
    #[Route('/activity', name: 'app_admin_logs_activity')]
    public function activityList(ActivityRepository $repo): Response
    {
        return $this->render('admin/logs/activity_list.html.twig', [
            'activities' => $repo->findAll(),
        ]);
    }

    #[Route('/', name: 'app_admin_logs_system')]
    public function systemLogs(): Response
    {
        return $this->render('admin/logs/system_list.html.twig', [
            'system' => [],
        ]);
    }
}

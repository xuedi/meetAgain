<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/{id}', name: 'app_admin_logs_system')]
    public function systemLogs(int $id = 0): Response
    {
        $logs = $this->getLogList();
        return $this->render('admin/logs/system_list.html.twig', [
            'content' => empty($logs) ? '' : $this->getLogContent($logs[$id]['source']),
            'logs' => $logs,
        ]);
    }

    private function getLogList(): array
    {
        $list = [];
        $logPath = realpath(__DIR__ . '/../../../var/log/');
        $logFiles = glob($logPath . '/*.log');

        foreach ($logFiles as $logFile) { // TODO: turn into array map function
            $nameChunks = explode('/', $logFile);
            $list[] = [
                'name' => end($nameChunks),
                'source' => $logFile,
            ];
        }

        return $list;
    }

    // TODO: add a level filter and split content with parser like: https://packagist.org/packages/innmind/log-reader
    private function getLogContent(string $path): string
    {
        return file_get_contents($path);
    }
}

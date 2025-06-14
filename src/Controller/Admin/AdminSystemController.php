<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Repository\ConfigRepository;
use App\Repository\ImageRepository;
use App\Repository\UserRepository;
use App\Service\ImageService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminSystemController extends AbstractController
{

    #[Route('/admin/system/config', name: 'app_admin_config')]
    public function configIndex(ConfigRepository $repo): Response
    {
        return $this->render('admin/config.html.twig', [
            'active' => 'config',
            'config' => $repo->findAll(),
        ]);
    }

    #[Route('/admin/system/redo-thumbnails', name: 'app_admin_redo_thumbnails')]
    public function thumbnailsIndex(ImageService $upload, ImageRepository $imageRepo): Response
    {
        $startTime = microtime(true);
        $cnt = 0;
        foreach ($imageRepo->findAll() as $image) {
            $cnt = $cnt + $upload->createThumbnails($image);
        }
        // TODO: do for for all images, and get the config from the setting and loop all source
        $executionTime = microtime(true) - $startTime;
        return $this->render('admin/thumbnails.html.twig', [
            'active' => 'redo',
            'count' => $cnt * 2, // 2 for each user,
            'time' => round($executionTime, 2)
        ]);
    }
}

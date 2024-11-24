<?php declare(strict_types=1);

namespace App\Controller;

use App\Service\CmsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/{catch_all}', name: 'app_catch_all', priority: -10)]
    public function index(Request $request, CmsService $cms): Response
    {
        return $cms->handle($request);
    }
}

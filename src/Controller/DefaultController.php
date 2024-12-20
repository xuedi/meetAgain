<?php declare(strict_types=1);

namespace App\Controller;

use App\Service\CmsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// TODO: make to get all pattern
class DefaultController extends AbstractController
{
    #[Route('/{slug}', name: 'app_catch_all', priority: -10)]
    public function index(Request $request, CmsService $cms, string $slug): Response
    {
        return $cms->handle($request->getLocale(), $slug);
    }
}

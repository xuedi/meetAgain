<?php declare(strict_types=1);

namespace App\Controller;

use App\Service\SitemapService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SitemapController extends AbstractController
{
    #[Route('/sitemap', name: 'app_sitemap')]
    public function index(Request $request,SitemapService $sitemap): Response
    {
        return $sitemap->getContent($request->getHost());
    }
}

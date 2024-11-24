<?php declare(strict_types=1);

namespace App\Service;

use App\Repository\CmsRepository;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class CmsService
{
    public function __construct(private Environment $twig, private CmsRepository $repo)
    {
    }

    public function handle(string $locale, string $slug): Response
    {
        $cms = $this->repo->findOneBy([
            'slug' => $slug,
            'published' => true
        ]);

        if ($cms === null) {
            return new Response($this->twig->render('cms/404.html.twig'), 200);
        }

        return new Response($this->twig->render('cms/index.html.twig', [
            'blocks' => $cms->getLanguageFilteredBlockJsonList($locale),
        ]), 200);
    }
}

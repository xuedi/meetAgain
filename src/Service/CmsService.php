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

    public function getSites(): array
    {
        return $this->repo->findAll();
    }

    public function handle(string $locale, string $slug): Response
    {
        $cms = $this->repo->findOneBy([
            'slug' => $slug,
            'published' => true
        ]);

        if ($cms === null) {
            return new Response($this->twig->render('cms/404.html.twig', [
                'message' => "These aren't the droids you're looking for!",
            ]), Response::HTTP_NOT_FOUND);
        }

        $blocks = $cms->getLanguageFilteredBlockJsonList($locale);
        if ($blocks->count() === 0) {
            return new Response($this->twig->render('cms/204.html.twig', [
                'message' => 'page was found but is has no content in this language',
            ]), Response::HTTP_NO_CONTENT);
        }

        return new Response($this->twig->render('cms/index.html.twig', [
            'blocks' => $blocks,
        ]), Response::HTTP_OK);
    }
}

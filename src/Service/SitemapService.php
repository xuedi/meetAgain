<?php declare(strict_types=1);

namespace App\Service;

use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

readonly class SitemapService
{
    public function __construct(private Environment $twig, private CmsService $cms)
    {
    }

    public function getContent(string $host): Response
    {
        $sites = [];
        foreach ($this->cms->getSites() as $site) {
            $sites[] = [
                'loc' => sprintf('https://%s/en/%s', $host, $site->getSlug()),
                'lastmod' => $site->getCreatedAt()->format('Y-m-d'),
                'prio' => 0.7,
            ];
        }

        foreach (['', 'events', 'members'] as $site) {
            $sites[] = [
                'loc' => sprintf('https://%s/en/%s', $host, $site),
                'lastmod' => (new DateTime())->format('Y-m-d'),
                'prio' => 0.9,
            ];
        }

        return new Response($this->twig->render('sitemap/index.xml.twig', [
            'sites' => $sites,
        ]), Response::HTTP_OK);
    }
}

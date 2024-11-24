<?php declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class CmsService
{
    public function __construct(private Environment $twig)
    {
    }

    public function handle(Request $request): Response
    {
        $uri = $request->getRequestUri();
        dump($uri);
        dump($request->getLocale());



        $contents = $this->twig->render('cms/index.html.twig', [
            'test' => 'testValue',
        ]);

        return new Response($contents, 200);
    }
}

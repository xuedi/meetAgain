<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ManageController extends AbstractController
{
    #[Route('/manage', name: 'app_manage')]
    public function number(): Response
    {
        $number = random_int(0, 100);

        return $this->render('manage/index.html.twig', [
            'number' => $number,
        ]);
    }
}

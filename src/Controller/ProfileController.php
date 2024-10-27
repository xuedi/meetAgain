<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function number(): Response
    {
        $number = random_int(0, 100);

        return $this->render('profile/index.html.twig', [
            'number' => $number,
        ]);
    }
}

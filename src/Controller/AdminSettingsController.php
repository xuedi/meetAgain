<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminSettingsController extends AbstractController
{
    #[Route('/admin/settings', name: 'app_admin_settings')]
    public function number(): Response
    {
        $number = random_int(0, 100);

        return $this->render('admin/settings.html.twig', [
            'number' => $number,
        ]);
    }
}

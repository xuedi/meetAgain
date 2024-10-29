<?php

namespace App\Controller;

use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminLocationController extends AbstractController
{
    #[Route('/admin/locations', name: 'app_admin_locations')]
    public function index(LocationRepository $repo): Response
    {
        return $this->render('admin/location.html.twig', [
            'locations' => $repo->findAll(),
        ]);
    }
}

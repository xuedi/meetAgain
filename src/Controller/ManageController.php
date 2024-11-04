<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ManageController extends AbstractController
{
    #[Route('/manage', name: 'app_manage')]
    public function index(): Response
    {
        return $this->render('manage/index.html.twig');
    }
}

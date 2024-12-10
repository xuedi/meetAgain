<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Host;
use App\Form\HostType;
use App\Repository\HostRepository;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/image')]
class AdminImageController extends AbstractController
{
    #[Route('/', name: 'app_admin_image')]
    public function imageList(ImageRepository $repo): Response
    {
        return $this->render('admin/image/list.html.twig', [
            'images' => $repo->findAll(),
        ]);
    }
}

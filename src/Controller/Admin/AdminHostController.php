<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Host;
use App\Form\HostType;
use App\Repository\HostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminHostController extends AbstractController
{
    #[Route('/admin/host/', name: 'app_admin_host')]
    public function hostList(HostRepository $repo): Response
    {
        return $this->render('admin/host/list.html.twig', [
            'hosts' => $repo->findAll(),
        ]);
    }
    #[Route('/admin/host/{id}', name: 'app_admin_host_edit', methods: ['GET', 'POST'])]
    public function hostEdit(Request $request, Host $host, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HostType::class, $host);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_host');
        }

        return $this->render('admin/host/edit.html.twig', [
            'host' => $host,
            'form' => $form,
        ]);
    }
}

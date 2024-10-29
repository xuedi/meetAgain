<?php

namespace App\Controller;

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
    #[Route('/admin/hosts', name: 'app_admin_hosts')]
    public function index(HostRepository $repo): Response
    {
        return $this->render('admin/host/list.html.twig', [
            'hosts' => $repo->findAll(),
        ]);
    }

    #[Route('/admin/hosts/{id}', name: 'app_admin_hosts_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Host $host, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HostType::class, $host);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_hosts');
        }

        return $this->render('admin/host/edit.html.twig', [
            'host' => $host,
            'form' => $form,
        ]);
    }
}

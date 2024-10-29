<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $repo
    ) {
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function index(): Response
    {
        return $this->render('admin/user/list.html.twig', [
            'users' => $this->repo->findAll(),
        ]);
    }

    #[Route('/admin/users/{id}', name: 'app_admin_users')]
    public function edit(int $id): Response
    {
        $user = $this->repo->findOneBy(['id' => $id]);

        $form = $this->createFormBuilder($user)
            ->add('user', User::class)
            ->getForm();

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form,
            'id' => $id,
        ]);
    }
}

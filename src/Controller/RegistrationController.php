<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserStatus;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($hasher->hashPassword($user, $plainPassword));
            $user->setRoles(['ROLE_USER']);
            $user->setStatus(UserStatus::Registered);
            $user->setPublic(true);
            $user->setLocale($request->getLocale());
            $user->setRegcode(sha1(random_bytes(128)));
            $user->setCreatedAt(new DateTimeImmutable());

            $em->persist($user);
            $em->flush();

            $email = new TemplatedEmail();
            $email->from(new Address('registration@dragon-descendants.de', 'Dragon Descendants Meetup'));
            $email->to((string) $user->getEmail());
            $email->subject('Please Confirm your Email');
            $email->htmlTemplate('registration/confirmation_email.html.twig');

            // generate a signed url and email it to the user
            //TODO: send an email

            return $this->render('registration/email_send.html.twig');
        }

        return $this->render('registration/index.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/register/verify/{code}', name: 'app_register_confirm_email')]
    public function verifyUserEmail(UserRepository $repo, EntityManagerInterface $em, string $code): Response
    {
        $user = $repo->findOneBy(['regcode' => $code]);
        if ($user===null) {
            return $this->render('registration/error.html.twig');
        }
        $user->setStatus(UserStatus::EmailVerified);
        $user->setRegcode(null);

        $em->persist($user);
        $em->flush();

        return $this->render('registration/success.html.twig');
    }
}

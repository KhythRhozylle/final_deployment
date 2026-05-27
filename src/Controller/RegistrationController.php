<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        EmailVerificationService $emailVerificationService,
        LoggerInterface $logger,
        Security $security,
    ): Response {

        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_admin_dashboard');
            }
            if ($this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_staff_home');
            }

            return $this->redirectToRoute('app_customer_portal');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Mark verified immediately — email is best-effort only (SMTP may be unavailable on Railway).
            $user->setIsVerified(true);
            $user->setVerificationToken(null);
            $user->setRoles([]); // ROLE_USER is added automatically by getRoles()

            $entityManager->persist($user);
            $entityManager->flush();

            // Try to send verification email but never block registration on SMTP failure.
            try {
                $verificationToken = $emailVerificationService->generateVerificationToken();
                $user->setIsVerified(false);
                $user->setVerificationToken($verificationToken);
                $entityManager->flush();

                $verificationUrl = $this->generateUrl(
                    'app_verify_email',
                    ['token' => $verificationToken],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $emailVerificationService->sendVerificationEmail($user, $verificationUrl);

                $this->addFlash('success', 'Registration successful! Please check your email to verify your account before logging in.');

                return $this->redirectToRoute('app_login');
            } catch (TransportExceptionInterface $e) {
                // SMTP unavailable — revert to verified so the user can log in immediately.
                $logger->error('Registration verification email failed — user kept as verified.', [
                    'exception' => $e,
                    'recipient' => $user->getEmail(),
                ]);
                $user->setIsVerified(true);
                $user->setVerificationToken(null);
                $entityManager->flush();
            }

            // Auto-login the user and send to the customer portal.
            $response = $security->login($user, 'form_login', 'main');

            $this->addFlash('success', 'Registration successful! Welcome to Florynn.');

            return $response ?? $this->redirectToRoute('app_customer_portal');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}

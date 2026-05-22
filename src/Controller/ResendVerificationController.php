<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class ResendVerificationController extends AbstractController
{
    #[Route('/resend-verification', name: 'app_resend_verification', methods: ['GET', 'POST'])]
    public function resend(
        Request $request,
        UserRepository $userRepository,
        EmailVerificationService $emailVerificationService,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger,
    ): Response {
        $email = $request->request->getString('email');

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('resend_verification', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid security token. Please try again.');
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Please enter a valid email address.');

                return $this->render('resend_verification/index.html.twig', [
                    'email' => $email,
                ]);
            }

            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user !== null && $user->isVerified() === true) {
                $this->addFlash('success', 'This email is already verified. You can sign in.');

                return $this->redirectToRoute('app_login');
            }

            if ($user !== null && $user->isVerified() !== true) {
                $verificationToken = $emailVerificationService->generateVerificationToken();
                $user->setVerificationToken($verificationToken);
                $entityManager->flush();

                $verificationUrl = $urlGenerator->generate(
                    'app_verify_email',
                    ['token' => $verificationToken],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                try {
                    $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
                } catch (TransportExceptionInterface $e) {
                    $logger->error('Resend verification email failed.', [
                        'exception' => $e,
                        'recipient' => $user->getEmail(),
                    ]);
                    $this->addFlash(
                        'error',
                        'We could not send mail right now. Try again later or contact support if it continues.'
                    );

                    return $this->render('resend_verification/index.html.twig', [
                        'email' => $email,
                    ]);
                }

                $this->addFlash(
                    'success',
                    'We sent a new verification link. Check your inbox and spam folder, then sign in after you verify.'
                );

                return $this->redirectToRoute('app_login');
            }

            $this->addFlash(
                'success',
                'If that address is registered and not yet verified, we sent a verification message. Check your inbox and spam folder.'
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('resend_verification/index.html.twig', [
            'email' => '',
        ]);
    }
}

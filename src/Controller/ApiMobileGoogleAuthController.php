<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\GoogleIdTokenVerifier;
use App\Service\MobileGoogleAuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiMobileGoogleAuthController extends AbstractController
{
    public function __construct(
        private readonly GoogleIdTokenVerifier $googleIdTokenVerifier,
        private readonly MobileGoogleAuthService $mobileGoogleAuthService,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    #[Route('/api/mobile/auth/google', name: 'api_mobile_auth_google', methods: ['POST'])]
    public function google(Request $request): JsonResponse
    {
        if ($this->getParameter('florynn.demo_mode')) {
            return $this->json([
                'error' => 'Demo mode',
                'message' => 'Google sign-in is disabled. Use /api/register and /api/login instead.',
            ], Response::HTTP_NOT_IMPLEMENTED);
        }

        $data = json_decode($request->getContent(), true);
        $idToken = \is_array($data) ? ($data['idToken'] ?? $data['id_token'] ?? null) : null;

        if (!\is_string($idToken) || trim($idToken) === '') {
            return $this->json([
                'error' => 'Validation failed',
                'message' => 'Google ID token is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $profile = $this->googleIdTokenVerifier->verify($idToken);
            $user = $this->mobileGoogleAuthService->resolveCustomerFromGoogleProfile(
                $profile['email'],
                $profile['name'],
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => 'Google sign-in failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => $this->serializeUser($user),
        ]);
    }

    /** @return array<string, mixed> */
    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
        ];
    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

class ApiLoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'error' => 'Invalid credentials',
                'message' => 'Authentication failed'
            ], 401);
        }

        return $this->json([
            'success' => true,
            'user' => $this->serializeMobileUser($user),
        ]);
    }

    /** @return array<string, mixed> */
    private function serializeMobileUser(\App\Entity\User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getUserIdentifier(),
            'username' => $user->getUsername(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'roleLabel' => $this->mobileRoleLabel($user),
            'isVerified' => $user->isVerified(),
        ];
    }

    private function mobileRoleLabel(\App\Entity\User $user): string
    {
        $stored = $user->getRoles();
        if (\in_array('ROLE_ADMIN', $stored, true) || \in_array('ROLE_STAFF', $stored, true)) {
            return $user->getPrimaryRoleLabel();
        }

        return 'User';
    }
}



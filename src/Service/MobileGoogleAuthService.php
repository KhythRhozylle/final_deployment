<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Customer-only Google sign-in for the OVALO mobile app.
 */
final class MobileGoogleAuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function resolveCustomerFromGoogleProfile(string $email, string $name): User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user instanceof User) {
            if ($this->isStaffOrAdmin($user)) {
                throw new \InvalidArgumentException(
                    'This Google account is linked to a staff profile. Use email and password in the admin portal.',
                );
            }

            if (!$user->isActive()) {
                throw new \InvalidArgumentException('Your account is disabled.');
            }

            if (!$user->isVerified()) {
                $user->setIsVerified(true);
                $user->setVerificationToken(null);
                $this->entityManager->flush();
            }

            return $user;
        }

        $newUser = new User();
        $newUser->setEmail($email);
        $newUser->setName($name);
        $newUser->setUsername($this->uniqueUsernameFromEmail($email));
        $newUser->setPassword($this->passwordHasher->hashPassword($newUser, bin2hex(random_bytes(32))));
        $newUser->setRoles([]);
        $newUser->setIsVerified(true);
        $newUser->setVerificationToken(null);

        $this->entityManager->persist($newUser);
        $this->entityManager->flush();

        return $newUser;
    }

    private function isStaffOrAdmin(User $user): bool
    {
        $roles = $user->getRoles();

        return \in_array('ROLE_STAFF', $roles, true)
            || \in_array('ROLE_ADMIN', $roles, true);
    }

    private function uniqueUsernameFromEmail(string $email): string
    {
        $local = strstr($email, '@', true) ?: 'user';
        $base = preg_replace('/[^a-zA-Z0-9_]/', '_', $local) ?? 'user';
        if ($base === '' || $base === '_') {
            $base = 'user';
        }
        $base = substr($base, 0, 180);
        $candidate = $base;
        for ($i = 0; $i < 64; ++$i) {
            $existing = $this->userRepository->findOneBy(['username' => $candidate]);
            if (null === $existing) {
                return $candidate;
            }
            $suffix = '_' . bin2hex(random_bytes(3));
            $candidate = substr($base, 0, max(1, 180 - \strlen($suffix))) . $suffix;
        }

        return 'user_' . bin2hex(random_bytes(12));
    }
}

<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = require __DIR__ . '/data/users.php';

        foreach ($rows as $row) {
            $user = new User();
            $user->setEmail((string) $row['email']);
            $user->setUsername((string) $row['username']);
            $user->setRoles(is_array($row['roles']) ? $row['roles'] : []);
            $user->setPassword((string) $row['password']);
            $user->setName((string) $row['name']);
            $user->setCreatedAt(new \DateTime((string) $row['created_at']));
            $user->setIsActive((bool) $row['is_active']);
            $user->setIsVerified((bool) $row['is_verified']);
            $user->setVerificationToken($row['verification_token'] !== null ? (string) $row['verification_token'] : null);

            $manager->persist($user);
            $this->addReference(FixtureReferences::user((int) $row['id']), $user);
        }

        $manager->flush();
    }
}

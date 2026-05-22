<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Entry point for loading all exported local database fixtures.
 *
 * Load order (via dependencies):
 *   User → Category, Customer, ActivityLog
 *   Category + User → Product
 *   User + Customer → Order
 *
 * Regenerate data from your DB:
 *   php scripts/export-fixture-data.php
 *
 * Load into an empty database (destructive):
 *   php bin/console doctrine:fixtures:load
 *
 * Append without purge (may fail on duplicate emails):
 *   php bin/console doctrine:fixtures:load --append
 */
class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public const ADMIN_USER_REFERENCE = 'user_1';

    public function load(ObjectManager $manager): void
    {
        // All entities are loaded by dedicated fixture classes.
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
            CategoryFixture::class,
            CustomerFixture::class,
            ProductFixture::class,
            OrderFixture::class,
            ActivityLogFixture::class,
        ];
    }
}

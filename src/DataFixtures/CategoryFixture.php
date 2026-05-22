<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CategoryFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = require __DIR__ . '/data/categories.php';

        foreach ($rows as $row) {
            $category = new Category();
            $category->setName((string) $row['name']);

            if (!empty($row['created_by_id'])) {
                $category->setCreatedBy($this->getReference(
                    FixtureReferences::user((int) $row['created_by_id']),
                    \App\Entity\User::class
                ));
            }

            $manager->persist($category);
            $this->addReference(FixtureReferences::category((int) $row['id']), $category);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class];
    }
}

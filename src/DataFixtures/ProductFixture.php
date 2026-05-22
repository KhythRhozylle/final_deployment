<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = require __DIR__ . '/data/products.php';

        foreach ($rows as $row) {
            $product = new Product();
            $product->setName((string) $row['name']);
            $product->setDescription((string) $row['description']);
            $product->setPrice((float) $row['price']);
            $product->setImage((string) $row['image']);
            $product->setStock((int) $row['stock']);

            if (!empty($row['category_id'])) {
                $product->setCategory($this->getReference(
                    FixtureReferences::category((int) $row['category_id']),
                    Category::class
                ));
            }

            if (!empty($row['created_by_id'])) {
                $product->setCreatedBy($this->getReference(
                    FixtureReferences::user((int) $row['created_by_id']),
                    User::class
                ));
            }

            $manager->persist($product);
            $this->addReference(FixtureReferences::product((int) $row['id']), $product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class, CategoryFixture::class];
    }
}

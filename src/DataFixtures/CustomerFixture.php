<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CustomerFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = require __DIR__ . '/data/customers.php';

        foreach ($rows as $row) {
            $customer = new Customer();
            $customer->setName((string) $row['name']);
            $customer->setEmail((string) $row['email']);
            $customer->setCustomerName((string) $row['customer_name']);
            $customer->setUsername((string) $row['username']);
            $customer->setPhone($row['phone'] !== null ? (string) $row['phone'] : null);
            $customer->setAddress($row['address'] !== null ? (string) $row['address'] : null);
            $customer->setDeliveryLocation($row['delivery_location'] !== null ? (string) $row['delivery_location'] : null);
            $customer->setCityProvince($row['city_province'] !== null ? (string) $row['city_province'] : null);

            if (!empty($row['created_by_id'])) {
                $customer->setCreatedBy($this->getReference(
                    FixtureReferences::user((int) $row['created_by_id']),
                    User::class
                ));
            }

            $manager->persist($customer);
            $this->addReference(FixtureReferences::customer((int) $row['id']), $customer);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class];
    }
}

<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = require __DIR__ . '/data/orders.php';

        foreach ($rows as $row) {
            $order = new Order();
            $order->setProductName((string) $row['product_name']);
            $order->setQuantity((float) $row['quantity']);
            $order->setPrice((float) $row['price']);
            $order->setStatus((string) $row['status']);
            $order->setOrderDate(new \DateTime((string) $row['order_date']));
            $order->setOrderGroupId($row['order_group_id'] !== null ? (string) $row['order_group_id'] : null);
            $order->setNotes($row['notes'] !== null ? (string) $row['notes'] : null);
            $order->setSource((string) ($row['source'] ?? 'staff'));
            $order->setProductId($row['product_id'] !== null ? (int) $row['product_id'] : null);
            $order->setStockDeducted((bool) ($row['stock_deducted'] ?? false));

            if (!empty($row['customer_id'])) {
                $order->setCustomer($this->getReference(
                    FixtureReferences::customer((int) $row['customer_id']),
                    Customer::class
                ));
            }

            if (!empty($row['created_by_id'])) {
                $order->setCreatedBy($this->getReference(
                    FixtureReferences::user((int) $row['created_by_id']),
                    User::class
                ));
            }

            $manager->persist($order);
            $this->addReference(FixtureReferences::order((int) $row['id']), $order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class, CustomerFixture::class];
    }
}

<?php

namespace App\DataFixtures;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ActivityLogFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = require __DIR__ . '/data/activity_logs.php';

        foreach ($rows as $row) {
            $log = new ActivityLog();
            $log->setAction((string) $row['action']);
            $log->setEntityType((string) $row['entity_type']);
            $log->setEntityId($row['entity_id'] !== null ? (int) $row['entity_id'] : null);
            $log->setAffectedData($row['affected_data'] !== null ? (string) $row['affected_data'] : null);
            $log->setDescription($row['description'] !== null ? (string) $row['description'] : null);
            $log->setTimestamp(new \DateTime((string) $row['timestamp']));
            $log->setIpAddress($row['ip_address'] !== null ? (string) $row['ip_address'] : null);

            if (!empty($row['user_id'])) {
                $log->setUser($this->getReference(
                    FixtureReferences::user((int) $row['user_id']),
                    User::class
                ));
            }

            $manager->persist($log);
            $this->addReference(FixtureReferences::activityLog((int) $row['id']), $log);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class];
    }
}

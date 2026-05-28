<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528104600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment fields to order table (method, status, proof path, reference number).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD payment_method VARCHAR(32) DEFAULT NULL, ADD payment_status VARCHAR(32) DEFAULT NULL, ADD payment_proof_path VARCHAR(255) DEFAULT NULL, ADD reference_number VARCHAR(128) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` DROP payment_method, DROP payment_status, DROP payment_proof_path, DROP reference_number');
    }
}


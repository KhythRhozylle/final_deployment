<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mobile ordering: customer delivery fields and order grouping';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer ADD address LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer ADD delivery_location VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE customer ADD city_province VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE `order` ADD order_group_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD notes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD source VARCHAR(20) DEFAULT \'staff\' NOT NULL');
        $this->addSql('CREATE INDEX IDX_ORDER_GROUP ON `order` (order_group_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_ORDER_GROUP ON `order`');
        $this->addSql('ALTER TABLE `order` DROP order_group_id');
        $this->addSql('ALTER TABLE `order` DROP notes');
        $this->addSql('ALTER TABLE `order` DROP source');

        $this->addSql('ALTER TABLE customer DROP address');
        $this->addSql('ALTER TABLE customer DROP delivery_location');
        $this->addSql('ALTER TABLE customer DROP city_province');
    }
}

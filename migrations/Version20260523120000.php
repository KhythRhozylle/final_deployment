<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Order product link and stock-deducted flag for mobile approval';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD stock_deducted TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` DROP product_id');
        $this->addSql('ALTER TABLE `order` DROP stock_deducted');
    }
}

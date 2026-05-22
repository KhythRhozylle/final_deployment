<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251014065522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // MySQL cannot drop `customer` while `order` still has a FK constraint referencing it.
        // Drop the FK first to avoid: "Cannot drop table 'customer' referenced by a foreign key constraint".
        $this->addSql("SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND CONSTRAINT_NAME = 'FK_F52993989395C3F3' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @sql = IF(@fk_exists > 0, 'ALTER TABLE `order` DROP FOREIGN KEY FK_F52993989395C3F3', 'SELECT 1')");
        $this->addSql("PREPARE stmt FROM @sql");
        $this->addSql("EXECUTE stmt");
        $this->addSql('DEALLOCATE PREPARE stmt');
        $this->addSql('DROP TABLE IF EXISTS customer');
        $this->addSql("SET @fk_product_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND CONSTRAINT_NAME = 'FK_D34A04AD12469DE2' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @sql_product = IF(@fk_product_exists > 0, 'ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2', 'SELECT 1')");
        $this->addSql("PREPARE stmt_product FROM @sql_product");
        $this->addSql("EXECUTE stmt_product");
        $this->addSql('DEALLOCATE PREPARE stmt_product');

        // MySQL does not support "DROP INDEX IF EXISTS ... ON ..." on all versions; emulate with information_schema.
        $this->addSql("SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND INDEX_NAME = 'IDX_D34A04AD12469DE2')");
        $this->addSql("SET @sql_idx = IF(@idx_exists > 0, 'DROP INDEX IDX_D34A04AD12469DE2 ON product', 'SELECT 1')");
        $this->addSql("PREPARE stmt_idx FROM @sql_idx");
        $this->addSql("EXECUTE stmt_idx");
        $this->addSql('DEALLOCATE PREPARE stmt_idx');

        // MySQL does not support "DROP COLUMN IF EXISTS" on all versions; emulate with information_schema.
        $this->addSql("SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = 'category_id')");
        $this->addSql("SET @sql_col = IF(@col_exists > 0, 'ALTER TABLE product DROP COLUMN category_id', 'SELECT 1')");
        $this->addSql("PREPARE stmt_col FROM @sql_col");
        $this->addSql("EXECUTE stmt_col");
        $this->addSql('DEALLOCATE PREPARE stmt_col');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, phone DOUBLE PRECISION NOT NULL, address VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE product ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)');
    }
}

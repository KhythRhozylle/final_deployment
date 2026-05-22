<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260320111941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Guard for partially-migrated schemas / reruns.
        $this->addSql("SET @customer_created_by_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND COLUMN_NAME = 'created_by_id')");
        $this->addSql("SET @sql_customer_created_by := IF(@customer_created_by_exists = 0, 'ALTER TABLE customer ADD created_by_id INT DEFAULT NULL', 'SELECT 1')");
        $this->addSql('PREPARE stmt_customer_created_by FROM @sql_customer_created_by');
        $this->addSql('EXECUTE stmt_customer_created_by');
        $this->addSql('DEALLOCATE PREPARE stmt_customer_created_by');

        $this->addSql("SET @customer_phone_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND COLUMN_NAME = 'phone')");
        $this->addSql("SET @sql_customer_phone := IF(@customer_phone_exists = 0, 'ALTER TABLE customer ADD phone VARCHAR(255) DEFAULT NULL', 'SELECT 1')");
        $this->addSql('PREPARE stmt_customer_phone FROM @sql_customer_phone');
        $this->addSql('EXECUTE stmt_customer_phone');
        $this->addSql('DEALLOCATE PREPARE stmt_customer_phone');

        // Adding a NOT NULL column on a non-empty table can fail; provide a safe default.
        $this->addSql("SET @customer_username_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND COLUMN_NAME = 'username')");
        $this->addSql("SET @sql_customer_username := IF(@customer_username_exists = 0, 'ALTER TABLE customer ADD username VARCHAR(255) NOT NULL DEFAULT ''''', 'SELECT 1')");
        $this->addSql('PREPARE stmt_customer_username FROM @sql_customer_username');
        $this->addSql('EXECUTE stmt_customer_username');
        $this->addSql('DEALLOCATE PREPARE stmt_customer_username');

        // FK + index only if missing (and if referenced table exists).
        $this->addSql("SET @user_tbl_exists := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user')");
        $this->addSql("SET @fk_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND CONSTRAINT_NAME = 'FK_81398E09B03A8386' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @sql_fk := IF(@user_tbl_exists > 0 AND @fk_exists = 0 AND @customer_created_by_exists > 0, 'ALTER TABLE customer ADD CONSTRAINT FK_81398E09B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk FROM @sql_fk');
        $this->addSql('EXECUTE stmt_fk');
        $this->addSql('DEALLOCATE PREPARE stmt_fk');

        $this->addSql("SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND INDEX_NAME = 'IDX_81398E09B03A8386')");
        $this->addSql("SET @sql_idx := IF(@idx_exists = 0 AND @customer_created_by_exists > 0, 'CREATE INDEX IDX_81398E09B03A8386 ON customer (created_by_id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_idx FROM @sql_idx');
        $this->addSql('EXECUTE stmt_idx');
        $this->addSql('DEALLOCATE PREPARE stmt_idx');

        // Only attempt to change `stock` type if the column exists.
        $this->addSql("SET @product_stock_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = 'stock')");
        $this->addSql("SET @sql_product_stock := IF(@product_stock_exists > 0, 'ALTER TABLE product CHANGE stock stock INT NOT NULL', 'SELECT 1')");
        $this->addSql('PREPARE stmt_product_stock FROM @sql_product_stock');
        $this->addSql('EXECUTE stmt_product_stock');
        $this->addSql('DEALLOCATE PREPARE stmt_product_stock');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("SET @product_stock_exists_down := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = 'stock')");
        $this->addSql("SET @sql_product_stock_down := IF(@product_stock_exists_down > 0, 'ALTER TABLE product CHANGE stock stock INT DEFAULT 0 NOT NULL', 'SELECT 1')");
        $this->addSql('PREPARE stmt_product_stock_down FROM @sql_product_stock_down');
        $this->addSql('EXECUTE stmt_product_stock_down');
        $this->addSql('DEALLOCATE PREPARE stmt_product_stock_down');

        $this->addSql("SET @fk_exists_down := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND CONSTRAINT_NAME = 'FK_81398E09B03A8386' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @sql_fk_down := IF(@fk_exists_down > 0, 'ALTER TABLE customer DROP FOREIGN KEY FK_81398E09B03A8386', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_down FROM @sql_fk_down');
        $this->addSql('EXECUTE stmt_fk_down');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_down');

        $this->addSql("SET @idx_exists_down := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND INDEX_NAME = 'IDX_81398E09B03A8386')");
        $this->addSql("SET @sql_idx_down := IF(@idx_exists_down > 0, 'DROP INDEX IDX_81398E09B03A8386 ON customer', 'SELECT 1')");
        $this->addSql('PREPARE stmt_idx_down FROM @sql_idx_down');
        $this->addSql('EXECUTE stmt_idx_down');
        $this->addSql('DEALLOCATE PREPARE stmt_idx_down');

        $this->addSql("SET @customer_created_by_exists_down := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND COLUMN_NAME = 'created_by_id')");
        $this->addSql("SET @sql_customer_created_by_down := IF(@customer_created_by_exists_down > 0, 'ALTER TABLE customer DROP COLUMN created_by_id', 'SELECT 1')");
        $this->addSql('PREPARE stmt_customer_created_by_down FROM @sql_customer_created_by_down');
        $this->addSql('EXECUTE stmt_customer_created_by_down');
        $this->addSql('DEALLOCATE PREPARE stmt_customer_created_by_down');

        $this->addSql("SET @customer_phone_exists_down := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND COLUMN_NAME = 'phone')");
        $this->addSql("SET @sql_customer_phone_down := IF(@customer_phone_exists_down > 0, 'ALTER TABLE customer DROP COLUMN phone', 'SELECT 1')");
        $this->addSql('PREPARE stmt_customer_phone_down FROM @sql_customer_phone_down');
        $this->addSql('EXECUTE stmt_customer_phone_down');
        $this->addSql('DEALLOCATE PREPARE stmt_customer_phone_down');

        $this->addSql("SET @customer_username_exists_down := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND COLUMN_NAME = 'username')");
        $this->addSql("SET @sql_customer_username_down := IF(@customer_username_exists_down > 0, 'ALTER TABLE customer DROP COLUMN username', 'SELECT 1')");
        $this->addSql('PREPARE stmt_customer_username_down FROM @sql_customer_username_down');
        $this->addSql('EXECUTE stmt_customer_username_down');
        $this->addSql('DEALLOCATE PREPARE stmt_customer_username_down');
    }
}

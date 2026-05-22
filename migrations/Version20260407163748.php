<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407163748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // This migration often collides with existing schemas; make it safe to re-run.
        $this->addSql('CREATE TABLE IF NOT EXISTS activity_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(255) NOT NULL, entity_type VARCHAR(255) NOT NULL, entity_id INT DEFAULT NULL, affected_data LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, timestamp DATETIME NOT NULL, ip_address VARCHAR(255) DEFAULT NULL, INDEX IDX_FD06F647A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS category (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_64C19C1B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS customer (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, customer_name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, INDEX IDX_81398E09B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS `order` (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, product_name VARCHAR(255) NOT NULL, quantity DOUBLE PRECISION NOT NULL, price DOUBLE PRECISION NOT NULL, status VARCHAR(255) NOT NULL, order_date DATETIME NOT NULL, INDEX IDX_F52993989395C3F3 (customer_id), INDEX IDX_F5299398B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS product (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, description LONGTEXT NOT NULL, image VARCHAR(255) NOT NULL, stock INT NOT NULL, INDEX IDX_D34A04AD12469DE2 (category_id), INDEX IDX_D34A04ADB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, is_verified TINYINT(1) NOT NULL, verification_token VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign keys only if missing and referenced tables exist.
        $this->addSql("SET @fk_activity_user_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_log' AND CONSTRAINT_NAME = 'FK_FD06F647A76ED395' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @sql_fk_activity_user := IF(@fk_activity_user_exists = 0, 'ALTER TABLE activity_log ADD CONSTRAINT FK_FD06F647A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_activity_user FROM @sql_fk_activity_user');
        $this->addSql('EXECUTE stmt_fk_activity_user');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_activity_user');

        $this->addSql("SET @fk_category_user_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'category' AND CONSTRAINT_NAME = 'FK_64C19C1B03A8386' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @category_created_by_col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'category' AND COLUMN_NAME = 'created_by_id')");
        $this->addSql("SET @sql_fk_category_user := IF(@fk_category_user_exists = 0 AND @category_created_by_col_exists > 0, 'ALTER TABLE category ADD CONSTRAINT FK_64C19C1B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_category_user FROM @sql_fk_category_user');
        $this->addSql('EXECUTE stmt_fk_category_user');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_category_user');

        $this->addSql("SET @fk_customer_user_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND CONSTRAINT_NAME = 'FK_81398E09B03A8386' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @customer_created_by_col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND COLUMN_NAME = 'created_by_id')");
        $this->addSql("SET @sql_fk_customer_user := IF(@fk_customer_user_exists = 0 AND @customer_created_by_col_exists > 0, 'ALTER TABLE customer ADD CONSTRAINT FK_81398E09B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_customer_user FROM @sql_fk_customer_user');
        $this->addSql('EXECUTE stmt_fk_customer_user');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_customer_user');

        $this->addSql("SET @fk_order_customer_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND CONSTRAINT_NAME = 'FK_F52993989395C3F3' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @order_customer_col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND COLUMN_NAME = 'customer_id')");
        $this->addSql("SET @sql_fk_order_customer := IF(@fk_order_customer_exists = 0 AND @order_customer_col_exists > 0, 'ALTER TABLE `order` ADD CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_order_customer FROM @sql_fk_order_customer');
        $this->addSql('EXECUTE stmt_fk_order_customer');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_order_customer');

        $this->addSql("SET @fk_order_user_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND CONSTRAINT_NAME = 'FK_F5299398B03A8386' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @order_created_by_col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND COLUMN_NAME = 'created_by_id')");
        $this->addSql("SET @sql_fk_order_user := IF(@fk_order_user_exists = 0 AND @order_created_by_col_exists > 0, 'ALTER TABLE `order` ADD CONSTRAINT FK_F5299398B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_order_user FROM @sql_fk_order_user');
        $this->addSql('EXECUTE stmt_fk_order_user');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_order_user');

        $this->addSql("SET @fk_product_category_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND CONSTRAINT_NAME = 'FK_D34A04AD12469DE2' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @product_category_col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = 'category_id')");
        $this->addSql("SET @sql_fk_product_category := IF(@fk_product_category_exists = 0 AND @product_category_col_exists > 0, 'ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_product_category FROM @sql_fk_product_category');
        $this->addSql('EXECUTE stmt_fk_product_category');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_product_category');

        $this->addSql("SET @fk_product_user_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND CONSTRAINT_NAME = 'FK_D34A04ADB03A8386' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @product_created_by_col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = 'created_by_id')");
        $this->addSql("SET @sql_fk_product_user := IF(@fk_product_user_exists = 0 AND @product_created_by_col_exists > 0, 'ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_product_user FROM @sql_fk_product_user');
        $this->addSql('EXECUTE stmt_fk_product_user');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_product_user');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_FD06F647A76ED395');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1B03A8386');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09B03A8386');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993989395C3F3');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398B03A8386');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADB03A8386');
        $this->addSql('DROP TABLE activity_log');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

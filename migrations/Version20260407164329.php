<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407164329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Guard for partially-migrated schemas / reruns.
        $this->addSql('CREATE TABLE IF NOT EXISTS category (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_64C19C1B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS customer (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, customer_name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, INDEX IDX_81398E09B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql("SET @category_created_by_col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'category' AND COLUMN_NAME = 'created_by_id')");
        $this->addSql("SET @fk_category_user_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'category' AND CONSTRAINT_NAME = 'FK_64C19C1B03A8386' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @sql_fk_category_user := IF(@fk_category_user_exists = 0 AND @category_created_by_col_exists > 0, 'ALTER TABLE category ADD CONSTRAINT FK_64C19C1B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_category_user FROM @sql_fk_category_user');
        $this->addSql('EXECUTE stmt_fk_category_user');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_category_user');

        $this->addSql("SET @customer_created_by_col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND COLUMN_NAME = 'created_by_id')");
        $this->addSql("SET @fk_customer_user_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND CONSTRAINT_NAME = 'FK_81398E09B03A8386' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @sql_fk_customer_user := IF(@fk_customer_user_exists = 0 AND @customer_created_by_col_exists > 0, 'ALTER TABLE customer ADD CONSTRAINT FK_81398E09B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_customer_user FROM @sql_fk_customer_user');
        $this->addSql('EXECUTE stmt_fk_customer_user');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_customer_user');

        $this->addSql("SET @fk_activity_log_user_old_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_log' AND CONSTRAINT_NAME = 'FK_ACTIVITY_LOG_USER' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @sql_drop_fk_activity_log_user_old := IF(@fk_activity_log_user_old_exists > 0, 'ALTER TABLE activity_log DROP FOREIGN KEY FK_ACTIVITY_LOG_USER', 'SELECT 1')");
        $this->addSql('PREPARE stmt_drop_fk_activity_log_user_old FROM @sql_drop_fk_activity_log_user_old');
        $this->addSql('EXECUTE stmt_drop_fk_activity_log_user_old');
        $this->addSql('DEALLOCATE PREPARE stmt_drop_fk_activity_log_user_old');

        $this->addSql("SET @fk_activity_log_user_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_log' AND CONSTRAINT_NAME = 'FK_FD06F647A76ED395' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @activity_log_user_col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_log' AND COLUMN_NAME = 'user_id')");
        $this->addSql("SET @sql_fk_activity_log_user := IF(@fk_activity_log_user_exists = 0 AND @activity_log_user_col_exists > 0, 'ALTER TABLE activity_log ADD CONSTRAINT FK_FD06F647A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_activity_log_user FROM @sql_fk_activity_log_user');
        $this->addSql('EXECUTE stmt_fk_activity_log_user');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_activity_log_user');

        $this->addSql("SET @idx_activity_log_old_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_log' AND INDEX_NAME = 'idx_activity_log_user')");
        $this->addSql("SET @idx_activity_log_new_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_log' AND INDEX_NAME = 'IDX_FD06F647A76ED395')");
        $this->addSql("SET @sql_rename_activity_log_idx := IF(@idx_activity_log_old_exists > 0 AND @idx_activity_log_new_exists = 0, 'ALTER TABLE activity_log RENAME INDEX idx_activity_log_user TO IDX_FD06F647A76ED395', 'SELECT 1')");
        $this->addSql('PREPARE stmt_rename_activity_log_idx FROM @sql_rename_activity_log_idx');
        $this->addSql('EXECUTE stmt_rename_activity_log_idx');
        $this->addSql('DEALLOCATE PREPARE stmt_rename_activity_log_idx');

        // `order` table adjustments (guarded).
        $this->addSql("SET @order_customer_id_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND COLUMN_NAME = 'customer_id')");
        $this->addSql("SET @order_created_by_id_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND COLUMN_NAME = 'created_by_id')");
        $this->addSql("SET @order_customer_name_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND COLUMN_NAME = 'customer_name')");
        $this->addSql("SET @sql_order_add_customer_id := IF(@order_customer_id_exists = 0, 'ALTER TABLE `order` ADD customer_id INT DEFAULT NULL', 'SELECT 1')");
        $this->addSql("SET @sql_order_add_created_by_id := IF(@order_created_by_id_exists = 0, 'ALTER TABLE `order` ADD created_by_id INT DEFAULT NULL', 'SELECT 1')");
        $this->addSql("SET @sql_order_drop_customer_name := IF(@order_customer_name_exists > 0, 'ALTER TABLE `order` DROP COLUMN customer_name', 'SELECT 1')");
        $this->addSql('PREPARE stmt_order_add_customer_id FROM @sql_order_add_customer_id');
        $this->addSql('EXECUTE stmt_order_add_customer_id');
        $this->addSql('DEALLOCATE PREPARE stmt_order_add_customer_id');
        $this->addSql('PREPARE stmt_order_add_created_by_id FROM @sql_order_add_created_by_id');
        $this->addSql('EXECUTE stmt_order_add_created_by_id');
        $this->addSql('DEALLOCATE PREPARE stmt_order_add_created_by_id');
        $this->addSql('PREPARE stmt_order_drop_customer_name FROM @sql_order_drop_customer_name');
        $this->addSql('EXECUTE stmt_order_drop_customer_name');
        $this->addSql('DEALLOCATE PREPARE stmt_order_drop_customer_name');

        $this->addSql("SET @fk_order_customer_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND CONSTRAINT_NAME = 'FK_F52993989395C3F3' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @sql_fk_order_customer := IF(@fk_order_customer_exists = 0 AND @order_customer_id_exists > 0, 'ALTER TABLE `order` ADD CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_order_customer FROM @sql_fk_order_customer');
        $this->addSql('EXECUTE stmt_fk_order_customer');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_order_customer');

        $this->addSql("SET @fk_order_user_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND CONSTRAINT_NAME = 'FK_F5299398B03A8386' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @sql_fk_order_user := IF(@fk_order_user_exists = 0 AND @order_created_by_id_exists > 0, 'ALTER TABLE `order` ADD CONSTRAINT FK_F5299398B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_order_user FROM @sql_fk_order_user');
        $this->addSql('EXECUTE stmt_fk_order_user');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_order_user');

        $this->addSql("SET @idx_order_customer_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND INDEX_NAME = 'IDX_F52993989395C3F3')");
        $this->addSql("SET @sql_idx_order_customer := IF(@idx_order_customer_exists = 0 AND @order_customer_id_exists > 0, 'CREATE INDEX IDX_F52993989395C3F3 ON `order` (customer_id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_idx_order_customer FROM @sql_idx_order_customer');
        $this->addSql('EXECUTE stmt_idx_order_customer');
        $this->addSql('DEALLOCATE PREPARE stmt_idx_order_customer');

        $this->addSql("SET @idx_order_created_by_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND INDEX_NAME = 'IDX_F5299398B03A8386')");
        $this->addSql("SET @sql_idx_order_created_by := IF(@idx_order_created_by_exists = 0 AND @order_created_by_id_exists > 0, 'CREATE INDEX IDX_F5299398B03A8386 ON `order` (created_by_id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_idx_order_created_by FROM @sql_idx_order_created_by');
        $this->addSql('EXECUTE stmt_idx_order_created_by');
        $this->addSql('DEALLOCATE PREPARE stmt_idx_order_created_by');

        // `product` adjustments (guarded).
        $this->addSql("SET @product_category_id_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = 'category_id')");
        $this->addSql("SET @product_created_by_id_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = 'created_by_id')");
        $this->addSql("SET @product_stock_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = 'stock')");
        $this->addSql("SET @product_product_name_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = 'product_name')");
        $this->addSql("SET @sql_product_add_category_id := IF(@product_category_id_exists = 0, 'ALTER TABLE product ADD category_id INT DEFAULT NULL', 'SELECT 1')");
        $this->addSql("SET @sql_product_add_created_by_id := IF(@product_created_by_id_exists = 0, 'ALTER TABLE product ADD created_by_id INT DEFAULT NULL', 'SELECT 1')");
        $this->addSql("SET @sql_product_add_stock := IF(@product_stock_exists = 0, 'ALTER TABLE product ADD stock INT NOT NULL DEFAULT 0', 'SELECT 1')");
        $this->addSql("SET @sql_product_rename_product_name := IF(@product_product_name_exists > 0, 'ALTER TABLE product CHANGE product_name name VARCHAR(255) NOT NULL', 'SELECT 1')");
        $this->addSql('PREPARE stmt_product_add_category_id FROM @sql_product_add_category_id');
        $this->addSql('EXECUTE stmt_product_add_category_id');
        $this->addSql('DEALLOCATE PREPARE stmt_product_add_category_id');
        $this->addSql('PREPARE stmt_product_add_created_by_id FROM @sql_product_add_created_by_id');
        $this->addSql('EXECUTE stmt_product_add_created_by_id');
        $this->addSql('DEALLOCATE PREPARE stmt_product_add_created_by_id');
        $this->addSql('PREPARE stmt_product_add_stock FROM @sql_product_add_stock');
        $this->addSql('EXECUTE stmt_product_add_stock');
        $this->addSql('DEALLOCATE PREPARE stmt_product_add_stock');
        $this->addSql('PREPARE stmt_product_rename_product_name FROM @sql_product_rename_product_name');
        $this->addSql('EXECUTE stmt_product_rename_product_name');
        $this->addSql('DEALLOCATE PREPARE stmt_product_rename_product_name');

        $this->addSql("SET @fk_product_category_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND CONSTRAINT_NAME = 'FK_D34A04AD12469DE2' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @sql_fk_product_category := IF(@fk_product_category_exists = 0 AND @product_category_id_exists > 0, 'ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_product_category FROM @sql_fk_product_category');
        $this->addSql('EXECUTE stmt_fk_product_category');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_product_category');

        $this->addSql("SET @fk_product_user_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND CONSTRAINT_NAME = 'FK_D34A04ADB03A8386' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @sql_fk_product_user := IF(@fk_product_user_exists = 0 AND @product_created_by_id_exists > 0, 'ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_product_user FROM @sql_fk_product_user');
        $this->addSql('EXECUTE stmt_fk_product_user');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_product_user');

        $this->addSql("SET @idx_product_category_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND INDEX_NAME = 'IDX_D34A04AD12469DE2')");
        $this->addSql("SET @sql_idx_product_category := IF(@idx_product_category_exists = 0 AND @product_category_id_exists > 0, 'CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_idx_product_category FROM @sql_idx_product_category');
        $this->addSql('EXECUTE stmt_idx_product_category');
        $this->addSql('DEALLOCATE PREPARE stmt_idx_product_category');

        $this->addSql("SET @idx_product_created_by_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND INDEX_NAME = 'IDX_D34A04ADB03A8386')");
        $this->addSql("SET @sql_idx_product_created_by := IF(@idx_product_created_by_exists = 0 AND @product_created_by_id_exists > 0, 'CREATE INDEX IDX_D34A04ADB03A8386 ON product (created_by_id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_idx_product_created_by FROM @sql_idx_product_created_by');
        $this->addSql('EXECUTE stmt_idx_product_created_by');
        $this->addSql('DEALLOCATE PREPARE stmt_idx_product_created_by');

        $this->addSql("SET @user_is_verified_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user' AND COLUMN_NAME = 'is_verified')");
        $this->addSql("SET @user_verification_token_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user' AND COLUMN_NAME = 'verification_token')");
        $this->addSql("SET @sql_user_add_is_verified := IF(@user_is_verified_exists = 0, 'ALTER TABLE user ADD is_verified TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1')");
        $this->addSql("SET @sql_user_add_verification_token := IF(@user_verification_token_exists = 0, 'ALTER TABLE user ADD verification_token VARCHAR(255) DEFAULT NULL', 'SELECT 1')");
        $this->addSql('PREPARE stmt_user_add_is_verified FROM @sql_user_add_is_verified');
        $this->addSql('EXECUTE stmt_user_add_is_verified');
        $this->addSql('DEALLOCATE PREPARE stmt_user_add_is_verified');
        $this->addSql('PREPARE stmt_user_add_verification_token FROM @sql_user_add_verification_token');
        $this->addSql('EXECUTE stmt_user_add_verification_token');
        $this->addSql('DEALLOCATE PREPARE stmt_user_add_verification_token');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993989395C3F3');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1B03A8386');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09B03A8386');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE customer');
        $this->addSql('ALTER TABLE `user` DROP is_verified, DROP verification_token');
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_FD06F647A76ED395');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT FK_ACTIVITY_LOG_USER FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE activity_log RENAME INDEX idx_fd06f647a76ed395 TO IDX_ACTIVITY_LOG_USER');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADB03A8386');
        $this->addSql('DROP INDEX IDX_D34A04AD12469DE2 ON product');
        $this->addSql('DROP INDEX IDX_D34A04ADB03A8386 ON product');
        $this->addSql('ALTER TABLE product DROP category_id, DROP created_by_id, DROP stock, CHANGE name product_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398B03A8386');
        $this->addSql('DROP INDEX IDX_F52993989395C3F3 ON `order`');
        $this->addSql('DROP INDEX IDX_F5299398B03A8386 ON `order`');
        $this->addSql('ALTER TABLE `order` ADD customer_name VARCHAR(255) NOT NULL, DROP customer_id, DROP created_by_id');
    }
}

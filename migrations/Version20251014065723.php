<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251014065723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // This migration may run against partially-migrated schemas. Guard each step for MySQL compatibility.
        $this->addSql("SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = 'category_id')");
        $this->addSql("SET @col_sql := IF(@col_exists = 0, 'ALTER TABLE product ADD category_id INT DEFAULT NULL', 'SELECT 1')");
        $this->addSql('PREPARE stmt_col FROM @col_sql');
        $this->addSql('EXECUTE stmt_col');
        $this->addSql('DEALLOCATE PREPARE stmt_col');

        // Add the FK only if the referenced table exists and the FK doesn't already exist.
        $this->addSql("SET @category_tbl_exists := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'category')");
        $this->addSql("SET @fk_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND CONSTRAINT_NAME = 'FK_D34A04AD12469DE2' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @fk_sql := IF(@category_tbl_exists > 0 AND @fk_exists = 0, 'ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk FROM @fk_sql');
        $this->addSql('EXECUTE stmt_fk');
        $this->addSql('DEALLOCATE PREPARE stmt_fk');

        // Add the index only if it doesn't exist.
        $this->addSql("SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND INDEX_NAME = 'IDX_D34A04AD12469DE2')");
        $this->addSql("SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt_idx FROM @idx_sql');
        $this->addSql('EXECUTE stmt_idx');
        $this->addSql('DEALLOCATE PREPARE stmt_idx');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("SET @fk_exists_down := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND CONSTRAINT_NAME = 'FK_D34A04AD12469DE2' AND CONSTRAINT_TYPE = 'FOREIGN KEY')");
        $this->addSql("SET @fk_sql_down := IF(@fk_exists_down > 0, 'ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2', 'SELECT 1')");
        $this->addSql('PREPARE stmt_fk_down FROM @fk_sql_down');
        $this->addSql('EXECUTE stmt_fk_down');
        $this->addSql('DEALLOCATE PREPARE stmt_fk_down');

        $this->addSql("SET @idx_exists_down := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND INDEX_NAME = 'IDX_D34A04AD12469DE2')");
        $this->addSql("SET @idx_sql_down := IF(@idx_exists_down > 0, 'DROP INDEX IDX_D34A04AD12469DE2 ON product', 'SELECT 1')");
        $this->addSql('PREPARE stmt_idx_down FROM @idx_sql_down');
        $this->addSql('EXECUTE stmt_idx_down');
        $this->addSql('DEALLOCATE PREPARE stmt_idx_down');

        $this->addSql("SET @col_exists_down := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = 'category_id')");
        $this->addSql("SET @col_sql_down := IF(@col_exists_down > 0, 'ALTER TABLE product DROP COLUMN category_id', 'SELECT 1')");
        $this->addSql('PREPARE stmt_col_down FROM @col_sql_down');
        $this->addSql('EXECUTE stmt_col_down');
        $this->addSql('DEALLOCATE PREPARE stmt_col_down');
    }
}

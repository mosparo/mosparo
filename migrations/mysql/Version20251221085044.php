<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251221085044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rule_package_processing_job (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, source_updated_at DATETIME DEFAULT NULL, type INT NOT NULL, mimetype VARCHAR(255) DEFAULT NULL, processed_import_data LONGTEXT NOT NULL, import_tasks INT NOT NULL, processed_import_tasks INT NOT NULL, processed_cleanup_data LONGTEXT NOT NULL, cleanup_tasks INT NOT NULL, processed_cleanup_tasks INT NOT NULL, rule_package_id INT DEFAULT NULL, project_id INT NOT NULL, INDEX IDX_4E75826FCFFD2724 (rule_package_id), INDEX IDX_4E75826F166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rule_package_processing_job ADD CONSTRAINT FK_4E75826FCFFD2724 FOREIGN KEY (rule_package_id) REFERENCES rule_package (id)');
        $this->addSql('ALTER TABLE rule_package_processing_job ADD CONSTRAINT FK_4E75826F166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE rule_package_cache CHANGE refreshed_at refreshed_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE rule_package_rule_cache ADD number_of_items INT DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE rule_package_rule_item_cache ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX rpric_rprc_project_idx ON rule_package_rule_item_cache (project_id, rule_package_rule_cache_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rule_package_processing_job DROP FOREIGN KEY FK_4E75826FCFFD2724');
        $this->addSql('ALTER TABLE rule_package_processing_job DROP FOREIGN KEY FK_4E75826F166D1F9C');
        $this->addSql('DROP TABLE rule_package_processing_job');
        $this->addSql('DROP INDEX rpric_rprc_project_idx ON rule_package_rule_item_cache');
        $this->addSql('ALTER TABLE rule_package_rule_item_cache DROP updated_at');
        $this->addSql('ALTER TABLE rule_package_rule_cache DROP number_of_items, DROP updated_at');
        $this->addSql('ALTER TABLE rule_package_cache CHANGE refreshed_at refreshed_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251221190529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rule_package_cache ADD COLUMN number_of_rules INTEGER DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__rule_package_cache AS SELECT id, refreshed_at, updated_at, refresh_interval, rule_package_id, project_id FROM rule_package_cache');
        $this->addSql('DROP TABLE rule_package_cache');
        $this->addSql('CREATE TABLE rule_package_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, refreshed_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, refresh_interval INTEGER NOT NULL, rule_package_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_7ECFE2B7CFFD2724 FOREIGN KEY (rule_package_id) REFERENCES rule_package (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7ECFE2B7166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO rule_package_cache (id, refreshed_at, updated_at, refresh_interval, rule_package_id, project_id) SELECT id, refreshed_at, updated_at, refresh_interval, rule_package_id, project_id FROM __temp__rule_package_cache');
        $this->addSql('DROP TABLE __temp__rule_package_cache');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ECFE2B7CFFD2724 ON rule_package_cache (rule_package_id)');
        $this->addSql('CREATE INDEX IDX_7ECFE2B7166D1F9C ON rule_package_cache (project_id)');
    }
}

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
        $this->addSql('CREATE TABLE rule_package_processing_job (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, source_updated_at DATETIME DEFAULT NULL, type INTEGER NOT NULL, mimetype VARCHAR(255) DEFAULT NULL, processed_import_data CLOB NOT NULL, import_tasks INTEGER NOT NULL, processed_import_tasks INTEGER NOT NULL, processed_cleanup_data CLOB NOT NULL, cleanup_tasks INTEGER NOT NULL, processed_cleanup_tasks INTEGER NOT NULL, rule_package_id INTEGER DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_4E75826FCFFD2724 FOREIGN KEY (rule_package_id) REFERENCES rule_package (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4E75826F166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4E75826FCFFD2724 ON rule_package_processing_job (rule_package_id)');
        $this->addSql('CREATE INDEX IDX_4E75826F166D1F9C ON rule_package_processing_job (project_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__rule_package_cache AS SELECT id, refreshed_at, updated_at, refresh_interval, rule_package_id, project_id FROM rule_package_cache');
        $this->addSql('DROP TABLE rule_package_cache');
        $this->addSql('CREATE TABLE rule_package_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, refreshed_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, refresh_interval INTEGER NOT NULL, rule_package_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_7ECFE2B7CFFD2724 FOREIGN KEY (rule_package_id) REFERENCES rule_package (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7ECFE2B7166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO rule_package_cache (id, refreshed_at, updated_at, refresh_interval, rule_package_id, project_id) SELECT id, refreshed_at, updated_at, refresh_interval, rule_package_id, project_id FROM __temp__rule_package_cache');
        $this->addSql('DROP TABLE __temp__rule_package_cache');
        $this->addSql('CREATE INDEX IDX_7ECFE2B7166D1F9C ON rule_package_cache (project_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ECFE2B7CFFD2724 ON rule_package_cache (rule_package_id)');
        $this->addSql('ALTER TABLE rule_package_rule_cache ADD COLUMN number_of_items INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE rule_package_rule_cache ADD COLUMN updated_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE TEMPORARY TABLE __temp__rule_package_rule_item_cache AS SELECT id, uuid, type, value, spam_rating_factor, prepared_value, hashed_value, rule_package_rule_cache_id, project_id FROM rule_package_rule_item_cache');
        $this->addSql('DROP TABLE rule_package_rule_item_cache');
        $this->addSql('CREATE TABLE rule_package_rule_item_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, type VARCHAR(50) NOT NULL, value CLOB NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, prepared_value CLOB DEFAULT NULL, hashed_value VARCHAR(32) DEFAULT NULL, rule_package_rule_cache_id INTEGER NOT NULL, project_id INTEGER NOT NULL, updated_at DATETIME DEFAULT NULL, CONSTRAINT FK_FD468764F19B63A2 FOREIGN KEY (rule_package_rule_cache_id) REFERENCES rule_package_rule_cache (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_FD468764166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO rule_package_rule_item_cache (id, uuid, type, value, spam_rating_factor, prepared_value, hashed_value, rule_package_rule_cache_id, project_id) SELECT id, uuid, type, value, spam_rating_factor, prepared_value, hashed_value, rule_package_rule_cache_id, project_id FROM __temp__rule_package_rule_item_cache');
        $this->addSql('DROP TABLE __temp__rule_package_rule_item_cache');
        $this->addSql('CREATE INDEX rpric_hashed_idx ON rule_package_rule_item_cache (project_id, type, hashed_value)');
        $this->addSql('CREATE INDEX rpric_uuid_idx ON rule_package_rule_item_cache (uuid)');
        $this->addSql('CREATE INDEX IDX_FD468764166D1F9C ON rule_package_rule_item_cache (project_id)');
        $this->addSql('CREATE INDEX IDX_FD468764F19B63A2 ON rule_package_rule_item_cache (rule_package_rule_cache_id)');
        $this->addSql('CREATE INDEX rpric_rprc_project_idx ON rule_package_rule_item_cache (project_id, rule_package_rule_cache_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE rule_package_processing_job');
        $this->addSql('CREATE TEMPORARY TABLE __temp__rule_package_cache AS SELECT id, refreshed_at, updated_at, refresh_interval, rule_package_id, project_id FROM rule_package_cache');
        $this->addSql('DROP TABLE rule_package_cache');
        $this->addSql('CREATE TABLE rule_package_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, refreshed_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, refresh_interval INTEGER NOT NULL, rule_package_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_7ECFE2B7CFFD2724 FOREIGN KEY (rule_package_id) REFERENCES rule_package (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7ECFE2B7166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO rule_package_cache (id, refreshed_at, updated_at, refresh_interval, rule_package_id, project_id) SELECT id, refreshed_at, updated_at, refresh_interval, rule_package_id, project_id FROM __temp__rule_package_cache');
        $this->addSql('DROP TABLE __temp__rule_package_cache');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ECFE2B7CFFD2724 ON rule_package_cache (rule_package_id)');
        $this->addSql('CREATE INDEX IDX_7ECFE2B7166D1F9C ON rule_package_cache (project_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__rule_package_rule_cache AS SELECT id, uuid, name, description, type, spam_rating_factor, rule_package_cache_id, project_id FROM rule_package_rule_cache');
        $this->addSql('DROP TABLE rule_package_rule_cache');
        $this->addSql('CREATE TABLE rule_package_rule_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, type VARCHAR(30) NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, rule_package_cache_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_90CF1D59C1C36394 FOREIGN KEY (rule_package_cache_id) REFERENCES rule_package_cache (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_90CF1D59166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO rule_package_rule_cache (id, uuid, name, description, type, spam_rating_factor, rule_package_cache_id, project_id) SELECT id, uuid, name, description, type, spam_rating_factor, rule_package_cache_id, project_id FROM __temp__rule_package_rule_cache');
        $this->addSql('DROP TABLE __temp__rule_package_rule_cache');
        $this->addSql('CREATE INDEX IDX_90CF1D59C1C36394 ON rule_package_rule_cache (rule_package_cache_id)');
        $this->addSql('CREATE INDEX IDX_90CF1D59166D1F9C ON rule_package_rule_cache (project_id)');
        $this->addSql('CREATE INDEX rprc_uuid_idx ON rule_package_rule_cache (uuid)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__rule_package_rule_item_cache AS SELECT id, uuid, type, value, spam_rating_factor, prepared_value, hashed_value, rule_package_rule_cache_id, project_id FROM rule_package_rule_item_cache');
        $this->addSql('DROP TABLE rule_package_rule_item_cache');
        $this->addSql('CREATE TABLE rule_package_rule_item_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, type VARCHAR(50) NOT NULL, value CLOB NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, prepared_value CLOB DEFAULT NULL, hashed_value VARCHAR(32) DEFAULT NULL, rule_package_rule_cache_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_FD468764F19B63A2 FOREIGN KEY (rule_package_rule_cache_id) REFERENCES rule_package_rule_cache (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_FD468764166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO rule_package_rule_item_cache (id, uuid, type, value, spam_rating_factor, prepared_value, hashed_value, rule_package_rule_cache_id, project_id) SELECT id, uuid, type, value, spam_rating_factor, prepared_value, hashed_value, rule_package_rule_cache_id, project_id FROM __temp__rule_package_rule_item_cache');
        $this->addSql('DROP TABLE __temp__rule_package_rule_item_cache');
        $this->addSql('CREATE INDEX IDX_FD468764F19B63A2 ON rule_package_rule_item_cache (rule_package_rule_cache_id)');
        $this->addSql('CREATE INDEX IDX_FD468764166D1F9C ON rule_package_rule_item_cache (project_id)');
        $this->addSql('CREATE INDEX rpric_uuid_idx ON rule_package_rule_item_cache (uuid)');
        $this->addSql('CREATE INDEX rpric_hashed_idx ON rule_package_rule_item_cache (project_id, type, hashed_value)');
    }
}

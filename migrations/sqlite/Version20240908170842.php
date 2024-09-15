<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240908170842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__project AS SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode, language_source FROM project');
        $this->addSql('DROP TABLE project');
        $this->addSql('CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, hosts CLOB DEFAULT NULL, public_key VARCHAR(64) NOT NULL, private_key CLOB NOT NULL, status SMALLINT NOT NULL, spam_score DOUBLE PRECISION NOT NULL, statistic_storage_limit VARCHAR(7) NOT NULL, api_debug_mode BOOLEAN NOT NULL, verification_simulation_mode BOOLEAN NOT NULL, language_source SMALLINT NOT NULL)');
        $this->addSql('INSERT INTO project (id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode, language_source) SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode, language_source FROM __temp__project');
        $this->addSql('DROP TABLE __temp__project');
        $this->addSql('ALTER TABLE submit_token ADD COLUMN proof_of_work_result VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__project AS SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode, language_source FROM project');
        $this->addSql('DROP TABLE project');
        $this->addSql('CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, hosts CLOB DEFAULT NULL, public_key VARCHAR(64) NOT NULL, private_key CLOB NOT NULL, status SMALLINT NOT NULL, spam_score DOUBLE PRECISION NOT NULL, statistic_storage_limit VARCHAR(7) NOT NULL, api_debug_mode BOOLEAN NOT NULL, verification_simulation_mode BOOLEAN NOT NULL, language_source SMALLINT DEFAULT 0 NOT NULL)');
        $this->addSql('INSERT INTO project (id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode, language_source) SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode, language_source FROM __temp__project');
        $this->addSql('DROP TABLE __temp__project');
        $this->addSql('CREATE TEMPORARY TABLE __temp__submit_token AS SELECT id, ip_address, page_title, page_url, token, created_at, checked_at, verified_at, valid_until, last_submission_id, project_id FROM submit_token');
        $this->addSql('DROP TABLE submit_token');
        $this->addSql('CREATE TABLE submit_token (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, page_title CLOB NOT NULL, page_url CLOB NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, checked_at DATETIME DEFAULT NULL, verified_at DATETIME DEFAULT NULL, valid_until DATETIME DEFAULT NULL, last_submission_id INTEGER DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_6C047AC88DF22AA4 FOREIGN KEY (last_submission_id) REFERENCES submission (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6C047AC8166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO submit_token (id, ip_address, page_title, page_url, token, created_at, checked_at, verified_at, valid_until, last_submission_id, project_id) SELECT id, ip_address, page_title, page_url, token, created_at, checked_at, verified_at, valid_until, last_submission_id, project_id FROM __temp__submit_token');
        $this->addSql('DROP TABLE __temp__submit_token');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6C047AC88DF22AA4 ON submit_token (last_submission_id)');
        $this->addSql('CREATE INDEX IDX_6C047AC8166D1F9C ON submit_token (project_id)');
    }
}

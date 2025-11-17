<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117111230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE security_guideline ADD COLUMN form_page_urls CLOB NOT NULL');
        $this->addSql('ALTER TABLE security_guideline ADD COLUMN form_action_urls CLOB NOT NULL');
        $this->addSql('ALTER TABLE security_guideline ADD COLUMN form_ids CLOB NOT NULL');
        $this->addSql('ALTER TABLE submit_token ADD COLUMN form_action_url CLOB DEFAULT NULL');
        $this->addSql('ALTER TABLE submit_token ADD COLUMN form_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__security_guideline AS SELECT id, uuid, name, description, priority, subnets, country_codes, as_numbers, project_id FROM security_guideline');
        $this->addSql('DROP TABLE security_guideline');
        $this->addSql('CREATE TABLE security_guideline (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, priority INTEGER NOT NULL, subnets CLOB NOT NULL, country_codes CLOB NOT NULL, as_numbers CLOB NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_7ECC68E5166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO security_guideline (id, uuid, name, description, priority, subnets, country_codes, as_numbers, project_id) SELECT id, uuid, name, description, priority, subnets, country_codes, as_numbers, project_id FROM __temp__security_guideline');
        $this->addSql('DROP TABLE __temp__security_guideline');
        $this->addSql('CREATE INDEX IDX_7ECC68E5166D1F9C ON security_guideline (project_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__submit_token AS SELECT id, ip_address, page_title, page_url, token, proof_of_work_result, created_at, checked_at, verified_at, valid_until, last_submission_id, project_id FROM submit_token');
        $this->addSql('DROP TABLE submit_token');
        $this->addSql('CREATE TABLE submit_token (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, page_title CLOB NOT NULL, page_url CLOB NOT NULL, token VARCHAR(64) NOT NULL, proof_of_work_result VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, checked_at DATETIME DEFAULT NULL, verified_at DATETIME DEFAULT NULL, valid_until DATETIME DEFAULT NULL, last_submission_id INTEGER DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_6C047AC88DF22AA4 FOREIGN KEY (last_submission_id) REFERENCES submission (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6C047AC8166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO submit_token (id, ip_address, page_title, page_url, token, proof_of_work_result, created_at, checked_at, verified_at, valid_until, last_submission_id, project_id) SELECT id, ip_address, page_title, page_url, token, proof_of_work_result, created_at, checked_at, verified_at, valid_until, last_submission_id, project_id FROM __temp__submit_token');
        $this->addSql('DROP TABLE __temp__submit_token');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6C047AC88DF22AA4 ON submit_token (last_submission_id)');
        $this->addSql('CREATE INDEX IDX_6C047AC8166D1F9C ON submit_token (project_id)');
        $this->addSql('CREATE INDEX st_token_idx ON submit_token (token)');
        $this->addSql('CREATE INDEX st_createdat_idx ON submit_token (created_at)');
    }
}

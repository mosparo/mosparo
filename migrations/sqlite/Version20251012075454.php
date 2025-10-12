<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251012075454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__cleanup_statistic AS SELECT id, date_time, cleanup_executor, number_of_stored_submit_tokens, number_of_deleted_submit_tokens, number_of_stored_submissions, number_of_deleted_submissions, execution_time, cleanup_status FROM cleanup_statistic');
        $this->addSql('DROP TABLE cleanup_statistic');
        $this->addSql('CREATE TABLE cleanup_statistic (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date_time DATETIME NOT NULL, cleanup_executor INTEGER NOT NULL, number_of_stored_submit_tokens INTEGER NOT NULL, number_of_deleted_submit_tokens INTEGER NOT NULL, number_of_stored_submissions INTEGER NOT NULL, number_of_deleted_submissions INTEGER NOT NULL, execution_time DOUBLE PRECISION NOT NULL, cleanup_status INTEGER NOT NULL)');
        $this->addSql('INSERT INTO cleanup_statistic (id, date_time, cleanup_executor, number_of_stored_submit_tokens, number_of_deleted_submit_tokens, number_of_stored_submissions, number_of_deleted_submissions, execution_time, cleanup_status) SELECT id, date_time, cleanup_executor, number_of_stored_submit_tokens, number_of_deleted_submit_tokens, number_of_stored_submissions, number_of_deleted_submissions, execution_time, cleanup_status FROM __temp__cleanup_statistic');
        $this->addSql('DROP TABLE __temp__cleanup_statistic');
        $this->addSql('CREATE INDEX cs_datetime_idx ON cleanup_statistic (date_time)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__delay AS SELECT id, ip_address, started_at, duration, valid_until FROM delay');
        $this->addSql('DROP TABLE delay');
        $this->addSql('CREATE TABLE delay (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, started_at DATETIME NOT NULL, duration INTEGER NOT NULL, valid_until DATETIME NOT NULL)');
        $this->addSql('INSERT INTO delay (id, ip_address, started_at, duration, valid_until) SELECT id, ip_address, started_at, duration, valid_until FROM __temp__delay');
        $this->addSql('DROP TABLE __temp__delay');
        $this->addSql('CREATE INDEX d_validuntil_idx ON delay (valid_until)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ip_localization AS SELECT id, ip_address, as_number, as_organization, country, cached_at FROM ip_localization');
        $this->addSql('DROP TABLE ip_localization');
        $this->addSql('CREATE TABLE ip_localization (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, as_number INTEGER DEFAULT NULL, as_organization VARCHAR(255) DEFAULT NULL, country VARCHAR(2) DEFAULT NULL, cached_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO ip_localization (id, ip_address, as_number, as_organization, country, cached_at) SELECT id, ip_address, as_number, as_organization, country, cached_at FROM __temp__ip_localization');
        $this->addSql('DROP TABLE __temp__ip_localization');
        $this->addSql('CREATE INDEX ip_ipaddress_idx ON ip_localization (ip_address)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__lockout AS SELECT id, ip_address, started_at, duration, valid_until FROM lockout');
        $this->addSql('DROP TABLE lockout');
        $this->addSql('CREATE TABLE lockout (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, started_at DATETIME NOT NULL, duration INTEGER NOT NULL, valid_until DATETIME NOT NULL)');
        $this->addSql('INSERT INTO lockout (id, ip_address, started_at, duration, valid_until) SELECT id, ip_address, started_at, duration, valid_until FROM __temp__lockout');
        $this->addSql('DROP TABLE __temp__lockout');
        $this->addSql('CREATE INDEX l_validuntil_idx ON lockout (valid_until)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__project AS SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode, language_source, project_group_id FROM project');
        $this->addSql('DROP TABLE project');
        $this->addSql('CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, hosts CLOB DEFAULT NULL, public_key VARCHAR(64) NOT NULL, private_key CLOB NOT NULL, status SMALLINT NOT NULL, spam_score DOUBLE PRECISION NOT NULL, statistic_storage_limit VARCHAR(7) NOT NULL, api_debug_mode BOOLEAN NOT NULL, verification_simulation_mode BOOLEAN NOT NULL, language_source SMALLINT NOT NULL, project_group_id INTEGER DEFAULT NULL, CONSTRAINT FK_2FB3D0EEC31A529C FOREIGN KEY (project_group_id) REFERENCES project_group (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO project (id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode, language_source, project_group_id) SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode, language_source, project_group_id FROM __temp__project');
        $this->addSql('DROP TABLE __temp__project');
        $this->addSql('CREATE INDEX IDX_2FB3D0EEC31A529C ON project (project_group_id)');
        $this->addSql('CREATE INDEX p_publickey_idx ON project (public_key)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__submission AS SELECT id, validation_token, data, signature, submitted_at, verified_at, matched_rule_items, ignored_fields, verified_fields, general_verifications, spam_rating, spam, spam_detection_rating, valid, submit_token_id, project_id, issues FROM submission');
        $this->addSql('DROP TABLE submission');
        $this->addSql('CREATE TABLE submission (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, validation_token VARCHAR(64) DEFAULT NULL, data CLOB NOT NULL, signature VARCHAR(64) DEFAULT NULL, submitted_at DATETIME NOT NULL, verified_at DATETIME DEFAULT NULL, matched_rule_items CLOB NOT NULL, ignored_fields CLOB NOT NULL, verified_fields CLOB NOT NULL, general_verifications CLOB NOT NULL, spam_rating DOUBLE PRECISION NOT NULL, spam BOOLEAN DEFAULT NULL, spam_detection_rating DOUBLE PRECISION NOT NULL, valid BOOLEAN DEFAULT NULL, submit_token_id INTEGER DEFAULT NULL, project_id INTEGER NOT NULL, issues CLOB DEFAULT NULL, CONSTRAINT FK_DB055AF32B4057C1 FOREIGN KEY (submit_token_id) REFERENCES submit_token (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DB055AF3166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO submission (id, validation_token, data, signature, submitted_at, verified_at, matched_rule_items, ignored_fields, verified_fields, general_verifications, spam_rating, spam, spam_detection_rating, valid, submit_token_id, project_id, issues) SELECT id, validation_token, data, signature, submitted_at, verified_at, matched_rule_items, ignored_fields, verified_fields, general_verifications, spam_rating, spam, spam_detection_rating, valid, submit_token_id, project_id, issues FROM __temp__submission');
        $this->addSql('DROP TABLE __temp__submission');
        $this->addSql('CREATE INDEX IDX_DB055AF3166D1F9C ON submission (project_id)');
        $this->addSql('CREATE INDEX IDX_DB055AF32B4057C1 ON submission (submit_token_id)');
        $this->addSql('CREATE INDEX s_submittedat_idx ON submission (submitted_at)');
        $this->addSql('CREATE INDEX s_spam_idx ON submission (spam)');
        $this->addSql('CREATE INDEX s_valid_idx ON submission (valid)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__submit_token AS SELECT id, ip_address, page_title, page_url, token, created_at, checked_at, verified_at, valid_until, last_submission_id, project_id, proof_of_work_result FROM submit_token');
        $this->addSql('DROP TABLE submit_token');
        $this->addSql('CREATE TABLE submit_token (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, page_title CLOB NOT NULL, page_url CLOB NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, checked_at DATETIME DEFAULT NULL, verified_at DATETIME DEFAULT NULL, valid_until DATETIME DEFAULT NULL, last_submission_id INTEGER DEFAULT NULL, project_id INTEGER NOT NULL, proof_of_work_result VARCHAR(64) DEFAULT NULL, CONSTRAINT FK_6C047AC88DF22AA4 FOREIGN KEY (last_submission_id) REFERENCES submission (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6C047AC8166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO submit_token (id, ip_address, page_title, page_url, token, created_at, checked_at, verified_at, valid_until, last_submission_id, project_id, proof_of_work_result) SELECT id, ip_address, page_title, page_url, token, created_at, checked_at, verified_at, valid_until, last_submission_id, project_id, proof_of_work_result FROM __temp__submit_token');
        $this->addSql('DROP TABLE __temp__submit_token');
        $this->addSql('CREATE INDEX IDX_6C047AC8166D1F9C ON submit_token (project_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6C047AC88DF22AA4 ON submit_token (last_submission_id)');
        $this->addSql('CREATE INDEX st_token_idx ON submit_token (token)');
        $this->addSql('CREATE INDEX st_createdat_idx ON submit_token (created_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__cleanup_statistic AS SELECT id, date_time, cleanup_executor, number_of_stored_submit_tokens, number_of_deleted_submit_tokens, number_of_stored_submissions, number_of_deleted_submissions, execution_time, cleanup_status FROM cleanup_statistic');
        $this->addSql('DROP TABLE cleanup_statistic');
        $this->addSql('CREATE TABLE cleanup_statistic (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date_time DATETIME NOT NULL, cleanup_executor INTEGER NOT NULL, number_of_stored_submit_tokens INTEGER NOT NULL, number_of_deleted_submit_tokens INTEGER NOT NULL, number_of_stored_submissions INTEGER NOT NULL, number_of_deleted_submissions INTEGER NOT NULL, execution_time DOUBLE PRECISION NOT NULL, cleanup_status INTEGER NOT NULL)');
        $this->addSql('INSERT INTO cleanup_statistic (id, date_time, cleanup_executor, number_of_stored_submit_tokens, number_of_deleted_submit_tokens, number_of_stored_submissions, number_of_deleted_submissions, execution_time, cleanup_status) SELECT id, date_time, cleanup_executor, number_of_stored_submit_tokens, number_of_deleted_submit_tokens, number_of_stored_submissions, number_of_deleted_submissions, execution_time, cleanup_status FROM __temp__cleanup_statistic');
        $this->addSql('DROP TABLE __temp__cleanup_statistic');
        $this->addSql('CREATE TEMPORARY TABLE __temp__delay AS SELECT id, ip_address, started_at, duration, valid_until FROM delay');
        $this->addSql('DROP TABLE delay');
        $this->addSql('CREATE TABLE delay (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, started_at DATETIME NOT NULL, duration INTEGER NOT NULL, valid_until DATETIME NOT NULL)');
        $this->addSql('INSERT INTO delay (id, ip_address, started_at, duration, valid_until) SELECT id, ip_address, started_at, duration, valid_until FROM __temp__delay');
        $this->addSql('DROP TABLE __temp__delay');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ip_localization AS SELECT id, ip_address, as_number, as_organization, country, cached_at FROM ip_localization');
        $this->addSql('DROP TABLE ip_localization');
        $this->addSql('CREATE TABLE ip_localization (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, as_number INTEGER DEFAULT NULL, as_organization VARCHAR(255) DEFAULT NULL, country VARCHAR(2) DEFAULT NULL, cached_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO ip_localization (id, ip_address, as_number, as_organization, country, cached_at) SELECT id, ip_address, as_number, as_organization, country, cached_at FROM __temp__ip_localization');
        $this->addSql('DROP TABLE __temp__ip_localization');
        $this->addSql('CREATE TEMPORARY TABLE __temp__lockout AS SELECT id, ip_address, started_at, duration, valid_until FROM lockout');
        $this->addSql('DROP TABLE lockout');
        $this->addSql('CREATE TABLE lockout (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, started_at DATETIME NOT NULL, duration INTEGER NOT NULL, valid_until DATETIME NOT NULL)');
        $this->addSql('INSERT INTO lockout (id, ip_address, started_at, duration, valid_until) SELECT id, ip_address, started_at, duration, valid_until FROM __temp__lockout');
        $this->addSql('DROP TABLE __temp__lockout');
        $this->addSql('CREATE TEMPORARY TABLE __temp__project AS SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode, language_source, project_group_id FROM project');
        $this->addSql('DROP TABLE project');
        $this->addSql('CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, hosts CLOB DEFAULT NULL, public_key VARCHAR(64) NOT NULL, private_key CLOB NOT NULL, status SMALLINT NOT NULL, spam_score DOUBLE PRECISION NOT NULL, statistic_storage_limit VARCHAR(7) NOT NULL, api_debug_mode BOOLEAN NOT NULL, verification_simulation_mode BOOLEAN NOT NULL, language_source SMALLINT NOT NULL, project_group_id INTEGER DEFAULT NULL, CONSTRAINT FK_2FB3D0EEC31A529C FOREIGN KEY (project_group_id) REFERENCES project_group (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO project (id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode, language_source, project_group_id) SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode, language_source, project_group_id FROM __temp__project');
        $this->addSql('DROP TABLE __temp__project');
        $this->addSql('CREATE INDEX IDX_2FB3D0EEC31A529C ON project (project_group_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__submission AS SELECT id, validation_token, data, signature, submitted_at, verified_at, matched_rule_items, ignored_fields, verified_fields, issues, general_verifications, spam_rating, spam, spam_detection_rating, valid, submit_token_id, project_id FROM submission');
        $this->addSql('DROP TABLE submission');
        $this->addSql('CREATE TABLE submission (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, validation_token VARCHAR(64) DEFAULT NULL, data CLOB NOT NULL, signature VARCHAR(64) DEFAULT NULL, submitted_at DATETIME NOT NULL, verified_at DATETIME DEFAULT NULL, matched_rule_items CLOB NOT NULL, ignored_fields CLOB NOT NULL, verified_fields CLOB NOT NULL, issues CLOB DEFAULT NULL, general_verifications CLOB NOT NULL, spam_rating DOUBLE PRECISION NOT NULL, spam BOOLEAN DEFAULT NULL, spam_detection_rating DOUBLE PRECISION NOT NULL, valid BOOLEAN DEFAULT NULL, submit_token_id INTEGER DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_DB055AF32B4057C1 FOREIGN KEY (submit_token_id) REFERENCES submit_token (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DB055AF3166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO submission (id, validation_token, data, signature, submitted_at, verified_at, matched_rule_items, ignored_fields, verified_fields, issues, general_verifications, spam_rating, spam, spam_detection_rating, valid, submit_token_id, project_id) SELECT id, validation_token, data, signature, submitted_at, verified_at, matched_rule_items, ignored_fields, verified_fields, issues, general_verifications, spam_rating, spam, spam_detection_rating, valid, submit_token_id, project_id FROM __temp__submission');
        $this->addSql('DROP TABLE __temp__submission');
        $this->addSql('CREATE INDEX IDX_DB055AF32B4057C1 ON submission (submit_token_id)');
        $this->addSql('CREATE INDEX IDX_DB055AF3166D1F9C ON submission (project_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__submit_token AS SELECT id, ip_address, page_title, page_url, token, proof_of_work_result, created_at, checked_at, verified_at, valid_until, last_submission_id, project_id FROM submit_token');
        $this->addSql('DROP TABLE submit_token');
        $this->addSql('CREATE TABLE submit_token (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, page_title CLOB NOT NULL, page_url CLOB NOT NULL, token VARCHAR(64) NOT NULL, proof_of_work_result VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, checked_at DATETIME DEFAULT NULL, verified_at DATETIME DEFAULT NULL, valid_until DATETIME DEFAULT NULL, last_submission_id INTEGER DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_6C047AC88DF22AA4 FOREIGN KEY (last_submission_id) REFERENCES submission (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6C047AC8166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO submit_token (id, ip_address, page_title, page_url, token, proof_of_work_result, created_at, checked_at, verified_at, valid_until, last_submission_id, project_id) SELECT id, ip_address, page_title, page_url, token, proof_of_work_result, created_at, checked_at, verified_at, valid_until, last_submission_id, project_id FROM __temp__submit_token');
        $this->addSql('DROP TABLE __temp__submit_token');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6C047AC88DF22AA4 ON submit_token (last_submission_id)');
        $this->addSql('CREATE INDEX IDX_6C047AC8166D1F9C ON submit_token (project_id)');
    }
}

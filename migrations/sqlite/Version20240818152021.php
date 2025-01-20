<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240818152021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE day_statistic (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, number_of_valid_submissions INTEGER NOT NULL, number_of_spam_submissions INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_3D2B35AC166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_3D2B35AC166D1F9C ON day_statistic (project_id)');
        $this->addSql('CREATE UNIQUE INDEX day_project_idx ON day_statistic (date, project_id)');
        $this->addSql('CREATE TABLE delay (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, started_at DATETIME NOT NULL, duration INTEGER NOT NULL, valid_until DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE ip_localization (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, as_number INTEGER DEFAULT NULL, as_organization VARCHAR(255) DEFAULT NULL, country VARCHAR(2) DEFAULT NULL, cached_at DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE lockout (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, started_at DATETIME NOT NULL, duration INTEGER NOT NULL, valid_until DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, hosts CLOB DEFAULT NULL, public_key VARCHAR(64) NOT NULL, private_key CLOB NOT NULL, status INTEGER NOT NULL, spam_score DOUBLE PRECISION NOT NULL, statistic_storage_limit VARCHAR(7) NOT NULL, api_debug_mode BOOLEAN NOT NULL, verification_simulation_mode BOOLEAN NOT NULL)');
        $this->addSql('CREATE TABLE project_config_value (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, value CLOB DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_8AFDEE17166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8AFDEE17166D1F9C ON project_config_value (project_id)');
        $this->addSql('CREATE TABLE project_member (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, role VARCHAR(30) NOT NULL, project_id INTEGER NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_67401132166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_67401132A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_67401132166D1F9C ON project_member (project_id)');
        $this->addSql('CREATE INDEX IDX_67401132A76ED395 ON project_member (user_id)');
        $this->addSql('CREATE TABLE reset_password_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7CE748AA76ED395 ON reset_password_request (user_id)');
        $this->addSql('CREATE TABLE rule (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, type VARCHAR(30) NOT NULL, status INTEGER NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_46D8ACCC166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_46D8ACCC166D1F9C ON rule (project_id)');
        $this->addSql('CREATE TABLE rule_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, type VARCHAR(255) NOT NULL, value CLOB NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, rule_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_4CDF7A69744E0351 FOREIGN KEY (rule_id) REFERENCES rule (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4CDF7A69166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4CDF7A69744E0351 ON rule_item (rule_id)');
        $this->addSql('CREATE INDEX IDX_4CDF7A69166D1F9C ON rule_item (project_id)');
        $this->addSql('CREATE TABLE ruleset (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, url CLOB NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, status BOOLEAN NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_F41BE3BD166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F41BE3BD166D1F9C ON ruleset (project_id)');
        $this->addSql('CREATE TABLE ruleset_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, refreshed_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, refresh_interval INTEGER NOT NULL, ruleset_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_378A314D54F1C144 FOREIGN KEY (ruleset_id) REFERENCES ruleset (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_378A314D166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_378A314D54F1C144 ON ruleset_cache (ruleset_id)');
        $this->addSql('CREATE INDEX IDX_378A314D166D1F9C ON ruleset_cache (project_id)');
        $this->addSql('CREATE TABLE ruleset_rule_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, type VARCHAR(30) NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, ruleset_cache_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_F140F8B5D533B618 FOREIGN KEY (ruleset_cache_id) REFERENCES ruleset_cache (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F140F8B5166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F140F8B5D533B618 ON ruleset_rule_cache (ruleset_cache_id)');
        $this->addSql('CREATE INDEX IDX_F140F8B5166D1F9C ON ruleset_rule_cache (project_id)');
        $this->addSql('CREATE TABLE ruleset_rule_item_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, type VARCHAR(255) NOT NULL, value CLOB NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, ruleset_rule_cache_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_674C1AEBF6DD31F FOREIGN KEY (ruleset_rule_cache_id) REFERENCES ruleset_rule_cache (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_674C1AEB166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_674C1AEBF6DD31F ON ruleset_rule_item_cache (ruleset_rule_cache_id)');
        $this->addSql('CREATE INDEX IDX_674C1AEB166D1F9C ON ruleset_rule_item_cache (project_id)');
        $this->addSql('CREATE TABLE security_guideline (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, priority INTEGER NOT NULL, subnets CLOB NOT NULL, country_codes CLOB NOT NULL, as_numbers CLOB NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_7ECC68E5166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7ECC68E5166D1F9C ON security_guideline (project_id)');
        $this->addSql('CREATE TABLE security_guideline_config_value (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, value CLOB DEFAULT NULL, security_guideline_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_24DA35EDF69EB831 FOREIGN KEY (security_guideline_id) REFERENCES security_guideline (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_24DA35ED166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_24DA35EDF69EB831 ON security_guideline_config_value (security_guideline_id)');
        $this->addSql('CREATE INDEX IDX_24DA35ED166D1F9C ON security_guideline_config_value (project_id)');
        $this->addSql('CREATE TABLE submission (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, validation_token VARCHAR(64) DEFAULT NULL, data CLOB NOT NULL, signature VARCHAR(64) DEFAULT NULL, submitted_at DATETIME NOT NULL, verified_at DATETIME DEFAULT NULL, matched_rule_items CLOB NOT NULL, ignored_fields CLOB NOT NULL, verified_fields CLOB NOT NULL, general_verifications CLOB NOT NULL, spam_rating DOUBLE PRECISION NOT NULL, spam BOOLEAN DEFAULT NULL, spam_detection_rating DOUBLE PRECISION NOT NULL, valid BOOLEAN DEFAULT NULL, submit_token_id INTEGER DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_DB055AF32B4057C1 FOREIGN KEY (submit_token_id) REFERENCES submit_token (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DB055AF3166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_DB055AF32B4057C1 ON submission (submit_token_id)');
        $this->addSql('CREATE INDEX IDX_DB055AF3166D1F9C ON submission (project_id)');
        $this->addSql('CREATE TABLE submit_token (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ip_address VARCHAR(128) NOT NULL, page_title CLOB NOT NULL, page_url CLOB NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, checked_at DATETIME DEFAULT NULL, verified_at DATETIME DEFAULT NULL, valid_until DATETIME DEFAULT NULL, last_submission_id INTEGER DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_6C047AC88DF22AA4 FOREIGN KEY (last_submission_id) REFERENCES submission (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6C047AC8166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6C047AC88DF22AA4 ON submit_token (last_submission_id)');
        $this->addSql('CREATE INDEX IDX_6C047AC8166D1F9C ON submit_token (project_id)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, googleAuthenticatorSecret VARCHAR(255) DEFAULT NULL, backup_codes CLOB NOT NULL, config_values CLOB NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE day_statistic');
        $this->addSql('DROP TABLE delay');
        $this->addSql('DROP TABLE ip_localization');
        $this->addSql('DROP TABLE lockout');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE project_config_value');
        $this->addSql('DROP TABLE project_member');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE rule');
        $this->addSql('DROP TABLE rule_item');
        $this->addSql('DROP TABLE ruleset');
        $this->addSql('DROP TABLE ruleset_cache');
        $this->addSql('DROP TABLE ruleset_rule_cache');
        $this->addSql('DROP TABLE ruleset_rule_item_cache');
        $this->addSql('DROP TABLE security_guideline');
        $this->addSql('DROP TABLE security_guideline_config_value');
        $this->addSql('DROP TABLE submission');
        $this->addSql('DROP TABLE submit_token');
        $this->addSql('DROP TABLE "user"');
    }
}

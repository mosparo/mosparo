<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220616122832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE delay (id INT AUTO_INCREMENT NOT NULL, ip_address VARCHAR(255) COMMENT \'(DC2Type:hashed)\' NOT NULL, started_at DATETIME NOT NULL, duration INT NOT NULL, valid_until DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ip_localization (id INT AUTO_INCREMENT NOT NULL, ip_address VARCHAR(255) COMMENT \'(DC2Type:hashed)\' NOT NULL, as_number INT DEFAULT NULL, as_organization VARCHAR(255) DEFAULT NULL, country VARCHAR(2) DEFAULT NULL, cached_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lockout (id INT AUTO_INCREMENT NOT NULL, ip_address VARCHAR(255) COMMENT \'(DC2Type:hashed)\' NOT NULL, started_at DATETIME NOT NULL, duration INT NOT NULL, valid_until DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, hosts LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', public_key VARCHAR(64) NOT NULL, private_key TEXT COMMENT \'(DC2Type:encrypted)\' NOT NULL, status INT NOT NULL, spam_score DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_config_value (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:object)\', INDEX IDX_8AFDEE17166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_member (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, user_id INT NOT NULL, role VARCHAR(30) NOT NULL, INDEX IDX_67401132166D1F9C (project_id), INDEX IDX_67401132A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rule (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(30) NOT NULL, status INT NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, INDEX IDX_46D8ACCC166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rule_item (id INT AUTO_INCREMENT NOT NULL, rule_id INT NOT NULL, project_id INT NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', type VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, INDEX IDX_4CDF7A69744E0351 (rule_id), INDEX IDX_4CDF7A69166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ruleset (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, url LONGTEXT NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, status TINYINT(1) NOT NULL, INDEX IDX_F41BE3BD166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ruleset_cache (id INT AUTO_INCREMENT NOT NULL, ruleset_id INT NOT NULL, project_id INT NOT NULL, refreshed_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, refresh_interval INT NOT NULL, UNIQUE INDEX UNIQ_378A314D54F1C144 (ruleset_id), INDEX IDX_378A314D166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ruleset_rule_cache (id INT AUTO_INCREMENT NOT NULL, ruleset_cache_id INT NOT NULL, project_id INT NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(30) NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, INDEX IDX_F140F8B5D533B618 (ruleset_cache_id), INDEX IDX_F140F8B5166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ruleset_rule_item_cache (id INT AUTO_INCREMENT NOT NULL, ruleset_rule_cache_id INT NOT NULL, project_id INT NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', type VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, INDEX IDX_674C1AEBF6DD31F (ruleset_rule_cache_id), INDEX IDX_674C1AEB166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE submission (id INT AUTO_INCREMENT NOT NULL, submit_token_id INT DEFAULT NULL, project_id INT NOT NULL, validation_token VARCHAR(64) DEFAULT NULL, data LONGTEXT COMMENT \'(DC2Type:encryptedJson)\' NOT NULL, signature VARCHAR(64) DEFAULT NULL, submitted_at DATETIME NOT NULL, verified_at DATETIME DEFAULT NULL, matched_rule_items LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', ignored_fields LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', general_verifications LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', spam_rating DOUBLE PRECISION NOT NULL, spam TINYINT(1) DEFAULT NULL, spam_detection_rating DOUBLE PRECISION NOT NULL, valid TINYINT(1) DEFAULT NULL, INDEX IDX_DB055AF32B4057C1 (submit_token_id), INDEX IDX_DB055AF3166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE submit_token (id INT AUTO_INCREMENT NOT NULL, submission_id INT DEFAULT NULL, project_id INT NOT NULL, ip_address VARCHAR(255) COMMENT \'(DC2Type:hashed)\' NOT NULL, page_title LONGTEXT NOT NULL, page_url LONGTEXT NOT NULL, signature VARCHAR(40) DEFAULT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, checked_at DATETIME DEFAULT NULL, verified_at DATETIME DEFAULT NULL, valid_until DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_6C047AC8E1FD4933 (submission_id), INDEX IDX_6C047AC8166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, googleAuthenticatorSecret VARCHAR(255) DEFAULT NULL, backup_codes LONGTEXT COMMENT \'(DC2Type:encryptedJson)\' NOT NULL, config_values LONGTEXT COMMENT \'(DC2Type:encryptedJson)\' NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_config_value ADD CONSTRAINT FK_8AFDEE17166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_member ADD CONSTRAINT FK_67401132166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_member ADD CONSTRAINT FK_67401132A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE rule ADD CONSTRAINT FK_46D8ACCC166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE rule_item ADD CONSTRAINT FK_4CDF7A69744E0351 FOREIGN KEY (rule_id) REFERENCES rule (id)');
        $this->addSql('ALTER TABLE rule_item ADD CONSTRAINT FK_4CDF7A69166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE ruleset ADD CONSTRAINT FK_F41BE3BD166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE ruleset_cache ADD CONSTRAINT FK_378A314D54F1C144 FOREIGN KEY (ruleset_id) REFERENCES ruleset (id)');
        $this->addSql('ALTER TABLE ruleset_cache ADD CONSTRAINT FK_378A314D166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE ruleset_rule_cache ADD CONSTRAINT FK_F140F8B5D533B618 FOREIGN KEY (ruleset_cache_id) REFERENCES ruleset_cache (id)');
        $this->addSql('ALTER TABLE ruleset_rule_cache ADD CONSTRAINT FK_F140F8B5166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE ruleset_rule_item_cache ADD CONSTRAINT FK_674C1AEBF6DD31F FOREIGN KEY (ruleset_rule_cache_id) REFERENCES ruleset_rule_cache (id)');
        $this->addSql('ALTER TABLE ruleset_rule_item_cache ADD CONSTRAINT FK_674C1AEB166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF32B4057C1 FOREIGN KEY (submit_token_id) REFERENCES submit_token (id)');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF3166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE submit_token ADD CONSTRAINT FK_6C047AC8E1FD4933 FOREIGN KEY (submission_id) REFERENCES submission (id)');
        $this->addSql('ALTER TABLE submit_token ADD CONSTRAINT FK_6C047AC8166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_config_value DROP FOREIGN KEY FK_8AFDEE17166D1F9C');
        $this->addSql('ALTER TABLE project_member DROP FOREIGN KEY FK_67401132166D1F9C');
        $this->addSql('ALTER TABLE rule DROP FOREIGN KEY FK_46D8ACCC166D1F9C');
        $this->addSql('ALTER TABLE rule_item DROP FOREIGN KEY FK_4CDF7A69166D1F9C');
        $this->addSql('ALTER TABLE ruleset DROP FOREIGN KEY FK_F41BE3BD166D1F9C');
        $this->addSql('ALTER TABLE ruleset_cache DROP FOREIGN KEY FK_378A314D166D1F9C');
        $this->addSql('ALTER TABLE ruleset_rule_cache DROP FOREIGN KEY FK_F140F8B5166D1F9C');
        $this->addSql('ALTER TABLE ruleset_rule_item_cache DROP FOREIGN KEY FK_674C1AEB166D1F9C');
        $this->addSql('ALTER TABLE submission DROP FOREIGN KEY FK_DB055AF3166D1F9C');
        $this->addSql('ALTER TABLE submit_token DROP FOREIGN KEY FK_6C047AC8166D1F9C');
        $this->addSql('ALTER TABLE rule_item DROP FOREIGN KEY FK_4CDF7A69744E0351');
        $this->addSql('ALTER TABLE ruleset_cache DROP FOREIGN KEY FK_378A314D54F1C144');
        $this->addSql('ALTER TABLE ruleset_rule_cache DROP FOREIGN KEY FK_F140F8B5D533B618');
        $this->addSql('ALTER TABLE ruleset_rule_item_cache DROP FOREIGN KEY FK_674C1AEBF6DD31F');
        $this->addSql('ALTER TABLE submit_token DROP FOREIGN KEY FK_6C047AC8E1FD4933');
        $this->addSql('ALTER TABLE submission DROP FOREIGN KEY FK_DB055AF32B4057C1');
        $this->addSql('ALTER TABLE project_member DROP FOREIGN KEY FK_67401132A76ED395');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
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
        $this->addSql('DROP TABLE submission');
        $this->addSql('DROP TABLE submit_token');
        $this->addSql('DROP TABLE user');
    }
}

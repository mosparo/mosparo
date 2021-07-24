<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210722175237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rule_item (id INT AUTO_INCREMENT NOT NULL, rule_id INT NOT NULL, project_id INT NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', type VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, INDEX IDX_4CDF7A69744E0351 (rule_id), INDEX IDX_4CDF7A69166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ruleset_rule_item_cache (id INT AUTO_INCREMENT NOT NULL, ruleset_rule_cache_id INT NOT NULL, project_id INT NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', type VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, INDEX IDX_674C1AEBF6DD31F (ruleset_rule_cache_id), INDEX IDX_674C1AEB166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rule_item ADD CONSTRAINT FK_4CDF7A69744E0351 FOREIGN KEY (rule_id) REFERENCES rule (id)');
        $this->addSql('ALTER TABLE rule_item ADD CONSTRAINT FK_4CDF7A69166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE ruleset_rule_item_cache ADD CONSTRAINT FK_674C1AEBF6DD31F FOREIGN KEY (ruleset_rule_cache_id) REFERENCES ruleset_rule_cache (id)');
        $this->addSql('ALTER TABLE ruleset_rule_item_cache ADD CONSTRAINT FK_674C1AEB166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE delay CHANGE ip_address ip_address VARCHAR(255) COMMENT \'(Hashed)\' NOT NULL');
        $this->addSql('ALTER TABLE ip_localization CHANGE ip_address ip_address VARCHAR(255) COMMENT \'(Hashed)\' NOT NULL');
        $this->addSql('ALTER TABLE lockout CHANGE ip_address ip_address VARCHAR(255) COMMENT \'(Hashed)\' NOT NULL');
        $this->addSql('ALTER TABLE project CHANGE private_key private_key TEXT COMMENT \'(Encrypted)\' NOT NULL');
        $this->addSql('ALTER TABLE rule DROP items');
        $this->addSql('ALTER TABLE ruleset_rule_cache DROP items');
        $this->addSql('ALTER TABLE submission CHANGE data data LONGTEXT COMMENT \'(EncryptedJson)\' NOT NULL');
        $this->addSql('ALTER TABLE submit_token CHANGE ip_address ip_address VARCHAR(255) COMMENT \'(Hashed)\' NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE backup_codes backup_codes LONGTEXT COMMENT \'(EncryptedJson)\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE rule_item');
        $this->addSql('DROP TABLE ruleset_rule_item_cache');
        $this->addSql('ALTER TABLE delay CHANGE ip_address ip_address VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(Hashed)\'');
        $this->addSql('ALTER TABLE ip_localization CHANGE ip_address ip_address VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(Hashed)\'');
        $this->addSql('ALTER TABLE lockout CHANGE ip_address ip_address VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(Hashed)\'');
        $this->addSql('ALTER TABLE project CHANGE private_key private_key TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(Encrypted)\'');
        $this->addSql('ALTER TABLE rule ADD items LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE ruleset_rule_cache ADD items LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE submission CHANGE data data LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(EncryptedJson)\'');
        $this->addSql('ALTER TABLE submit_token CHANGE ip_address ip_address VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(Hashed)\'');
        $this->addSql('ALTER TABLE user CHANGE backup_codes backup_codes LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\'');
    }
}

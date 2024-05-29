<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240218182953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delay CHANGE ip_address ip_address VARCHAR(128) NOT NULL');
        $this->addSql('ALTER TABLE ip_localization CHANGE ip_address ip_address VARCHAR(128) NOT NULL');
        $this->addSql('ALTER TABLE lockout CHANGE ip_address ip_address VARCHAR(128) NOT NULL');
        $this->addSql('ALTER TABLE project CHANGE hosts hosts LONGTEXT DEFAULT NULL, CHANGE uuid uuid CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE project_config_value CHANGE value value LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reset_password_request CHANGE requested_at requested_at DATETIME NOT NULL, CHANGE expires_at expires_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE rule CHANGE uuid uuid CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE rule_item CHANGE uuid uuid CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE ruleset_rule_cache CHANGE uuid uuid CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE ruleset_rule_item_cache CHANGE uuid uuid CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE security_guideline CHANGE uuid uuid CHAR(36) NOT NULL, CHANGE subnets subnets JSON NOT NULL, CHANGE country_codes country_codes JSON NOT NULL, CHANGE as_numbers as_numbers JSON NOT NULL');
        $this->addSql('ALTER TABLE security_guideline_config_value CHANGE value value JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE submission CHANGE data data LONGTEXT NOT NULL, CHANGE matched_rule_items matched_rule_items JSON NOT NULL, CHANGE ignored_fields ignored_fields JSON NOT NULL, CHANGE general_verifications general_verifications JSON NOT NULL, CHANGE verified_fields verified_fields JSON NOT NULL');
        $this->addSql('ALTER TABLE submit_token CHANGE ip_address ip_address VARCHAR(128) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL, CHANGE backup_codes backup_codes LONGTEXT NOT NULL, CHANGE config_values config_values LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE submission CHANGE data data LONGTEXT NOT NULL COMMENT \'(DC2Type:encryptedJson)\', CHANGE matched_rule_items matched_rule_items JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE ignored_fields ignored_fields JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE verified_fields verified_fields JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE general_verifications general_verifications JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE submit_token CHANGE ip_address ip_address VARCHAR(255) NOT NULL COMMENT \'(DC2Type:hashed)\'');
        $this->addSql('ALTER TABLE ip_localization CHANGE ip_address ip_address VARCHAR(255) NOT NULL COMMENT \'(DC2Type:hashed)\'');
        $this->addSql('ALTER TABLE ruleset_rule_item_cache CHANGE uuid uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE rule CHANGE uuid uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE `user` CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE backup_codes backup_codes LONGTEXT NOT NULL COMMENT \'(DC2Type:encryptedJson)\', CHANGE config_values config_values LONGTEXT NOT NULL COMMENT \'(DC2Type:encryptedJson)\'');
        $this->addSql('ALTER TABLE delay CHANGE ip_address ip_address VARCHAR(255) NOT NULL COMMENT \'(DC2Type:hashed)\'');
        $this->addSql('ALTER TABLE rule_item CHANGE uuid uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE reset_password_request CHANGE requested_at requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE ruleset_rule_cache CHANGE uuid uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE security_guideline_config_value CHANGE value value JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE lockout CHANGE ip_address ip_address VARCHAR(255) NOT NULL COMMENT \'(DC2Type:hashed)\'');
        $this->addSql('ALTER TABLE security_guideline CHANGE uuid uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE subnets subnets JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE country_codes country_codes JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE as_numbers as_numbers JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE project CHANGE uuid uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE hosts hosts LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE project_config_value CHANGE value value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:object)\'');
    }
}

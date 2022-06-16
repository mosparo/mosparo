<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220206160523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delay CHANGE ip_address ip_address VARCHAR(255) COMMENT \'(Hashed)\' NOT NULL');
        $this->addSql('ALTER TABLE ip_localization CHANGE ip_address ip_address VARCHAR(255) COMMENT \'(Hashed)\' NOT NULL');
        $this->addSql('ALTER TABLE lockout CHANGE ip_address ip_address VARCHAR(255) COMMENT \'(Hashed)\' NOT NULL');
        $this->addSql('ALTER TABLE project CHANGE private_key private_key TEXT COMMENT \'(Encrypted)\' NOT NULL');
        $this->addSql('ALTER TABLE submission ADD general_verifications LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', CHANGE data data LONGTEXT COMMENT \'(EncryptedJson)\' NOT NULL');
        $this->addSql('ALTER TABLE submit_token CHANGE ip_address ip_address VARCHAR(255) COMMENT \'(Hashed)\' NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE backup_codes backup_codes LONGTEXT COMMENT \'(EncryptedJson)\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delay CHANGE ip_address ip_address VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(Hashed)\'');
        $this->addSql('ALTER TABLE ip_localization CHANGE ip_address ip_address VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(Hashed)\', CHANGE as_organization as_organization VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE country country VARCHAR(2) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE lockout CHANGE ip_address ip_address VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(Hashed)\'');
        $this->addSql('ALTER TABLE project CHANGE name name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE hosts hosts LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', CHANGE public_key public_key VARCHAR(64) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE private_key private_key TEXT NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(Encrypted)\', CHANGE config_values config_values LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE project_member CHANGE role role VARCHAR(30) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE reset_password_request CHANGE selector selector VARCHAR(20) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE hashed_token hashed_token VARCHAR(100) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE rule CHANGE uuid uuid CHAR(36) NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE name name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE type type VARCHAR(30) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE rule_item CHANGE uuid uuid CHAR(36) NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE type type VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE value value LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ruleset CHANGE name name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE url url LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ruleset_rule_cache CHANGE uuid uuid CHAR(36) NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE name name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE type type VARCHAR(30) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ruleset_rule_item_cache CHANGE uuid uuid CHAR(36) NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE type type VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE value value LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE submission DROP general_verifications, CHANGE validation_token validation_token VARCHAR(64) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE data data LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(EncryptedJson)\', CHANGE signature signature VARCHAR(64) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE matched_rule_items matched_rule_items LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', CHANGE ignored_fields ignored_fields LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE submit_token CHANGE ip_address ip_address VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(Hashed)\', CHANGE page_title page_title LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE page_url page_url LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE signature signature VARCHAR(40) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE token token VARCHAR(64) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE email email VARCHAR(180) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', CHANGE password password VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE googleAuthenticatorSecret googleAuthenticatorSecret VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE backup_codes backup_codes LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(EncryptedJson)\'');
    }
}

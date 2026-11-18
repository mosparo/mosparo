<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261117093904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE webauthn_credentials (public_key_credential_id CLOB NOT NULL, type VARCHAR(255) NOT NULL, transports CLOB NOT NULL, attestation_type VARCHAR(255) NOT NULL, trust_path CLOB NOT NULL, aaguid CLOB NOT NULL, credential_public_key CLOB NOT NULL, user_handle VARCHAR(255) NOT NULL, counter INTEGER NOT NULL, other_ui CLOB DEFAULT NULL, backup_eligible BOOLEAN DEFAULT NULL, backup_status BOOLEAN DEFAULT NULL, uv_initialized BOOLEAN DEFAULT NULL, id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, googleAuthenticatorSecret, backup_codes, config_values FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) DEFAULT NULL, googleAuthenticatorSecret VARCHAR(255) DEFAULT NULL, backup_codes CLOB NOT NULL, config_values CLOB NOT NULL, auth_source SMALLINT DEFAULT NULL, auth_method VARCHAR(255) DEFAULT NULL, user_handle VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, googleAuthenticatorSecret, backup_codes, config_values) SELECT id, email, roles, password, googleAuthenticatorSecret, backup_codes, config_values FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        // Set all users to AuthSource::LOCAL
        $this->addSql('UPDATE user SET auth_source = 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE webauthn_credentials');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, googleAuthenticatorSecret, backup_codes, config_values FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, googleAuthenticatorSecret VARCHAR(255) DEFAULT NULL, backup_codes CLOB NOT NULL, config_values CLOB NOT NULL)');
        $this->addSql('INSERT INTO "user" (id, email, roles, password, googleAuthenticatorSecret, backup_codes, config_values) SELECT id, email, roles, password, googleAuthenticatorSecret, backup_codes, config_values FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
    }
}

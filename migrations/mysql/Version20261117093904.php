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
        $this->addSql('CREATE TABLE webauthn_credentials (public_key_credential_id LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, transports JSON NOT NULL, attestation_type VARCHAR(255) NOT NULL, trust_path JSON NOT NULL, aaguid TINYTEXT NOT NULL, credential_public_key LONGTEXT NOT NULL, user_handle VARCHAR(255) NOT NULL, counter INT NOT NULL, other_ui JSON DEFAULT NULL, backup_eligible TINYINT DEFAULT NULL, backup_status TINYINT DEFAULT NULL, uv_initialized TINYINT DEFAULT NULL, id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user ADD auth_source SMALLINT DEFAULT NULL, ADD auth_method VARCHAR(255) DEFAULT NULL, ADD user_handle VARCHAR(255) DEFAULT NULL, CHANGE password password VARCHAR(255) DEFAULT NULL');
        // Set all users to AuthSource::LOCAL
        $this->addSql('UPDATE user SET auth_source = 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE webauthn_credentials');
        $this->addSql('ALTER TABLE `user` DROP auth_source, DROP auth_method, DROP user_handle, CHANGE password password VARCHAR(255) NOT NULL');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}

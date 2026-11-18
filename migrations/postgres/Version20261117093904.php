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
        $this->addSql('CREATE TABLE webauthn_credentials (public_key_credential_id TEXT NOT NULL, type VARCHAR(255) NOT NULL, transports JSON NOT NULL, attestation_type VARCHAR(255) NOT NULL, trust_path JSON NOT NULL, aaguid TEXT NOT NULL, credential_public_key TEXT NOT NULL, user_handle VARCHAR(255) NOT NULL, counter INT NOT NULL, other_ui JSON DEFAULT NULL, backup_eligible BOOLEAN DEFAULT NULL, backup_status BOOLEAN DEFAULT NULL, uv_initialized BOOLEAN DEFAULT NULL, id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE "user" ADD auth_source SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD auth_method VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD user_handle VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ALTER password DROP NOT NULL');
        // Set all users to AuthSource::LOCAL
        $this->addSql('UPDATE "user" SET auth_source = 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE webauthn_credentials');
        $this->addSql('ALTER TABLE "user" DROP auth_source');
        $this->addSql('ALTER TABLE "user" DROP auth_method');
        $this->addSql('ALTER TABLE "user" DROP user_handle');
        $this->addSql('ALTER TABLE "user" ALTER password SET NOT NULL');
    }
}

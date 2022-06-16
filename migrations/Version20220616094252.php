<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220616094252 extends AbstractMigration
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
        $this->addSql('ALTER TABLE project_config_value CHANGE value value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:object)\'');
        $this->addSql('ALTER TABLE submission CHANGE data data LONGTEXT COMMENT \'(EncryptedJson)\' NOT NULL');
        $this->addSql('ALTER TABLE submit_token CHANGE ip_address ip_address VARCHAR(255) COMMENT \'(Hashed)\' NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE backup_codes backup_codes LONGTEXT COMMENT \'(EncryptedJson)\' NOT NULL, CHANGE config_values config_values LONGTEXT COMMENT \'(EncryptedJson)\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delay CHANGE ip_address ip_address VARCHAR(255) NOT NULL COMMENT \'(Hashed)\'');
        $this->addSql('ALTER TABLE ip_localization CHANGE ip_address ip_address VARCHAR(255) NOT NULL COMMENT \'(Hashed)\'');
        $this->addSql('ALTER TABLE lockout CHANGE ip_address ip_address VARCHAR(255) NOT NULL COMMENT \'(Hashed)\'');
        $this->addSql('ALTER TABLE project CHANGE private_key private_key TEXT NOT NULL COMMENT \'(Encrypted)\'');
        $this->addSql('ALTER TABLE project_config_value CHANGE value value LONGBLOB DEFAULT NULL');
        $this->addSql('ALTER TABLE submission CHANGE data data LONGTEXT NOT NULL COMMENT \'(EncryptedJson)\'');
        $this->addSql('ALTER TABLE submit_token CHANGE ip_address ip_address VARCHAR(255) NOT NULL COMMENT \'(Hashed)\'');
        $this->addSql('ALTER TABLE user CHANGE backup_codes backup_codes LONGTEXT NOT NULL COMMENT \'(EncryptedJson)\', CHANGE config_values config_values LONGTEXT NOT NULL COMMENT \'(EncryptedJson)\'');
    }
}

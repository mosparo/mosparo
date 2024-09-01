<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240827173742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__project AS SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode FROM project');
        $this->addSql('DROP TABLE project');
        $this->addSql('CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, hosts CLOB DEFAULT NULL, public_key VARCHAR(64) NOT NULL, private_key CLOB NOT NULL, status SMALLINT NOT NULL, spam_score DOUBLE PRECISION NOT NULL, statistic_storage_limit VARCHAR(7) NOT NULL, api_debug_mode BOOLEAN NOT NULL, verification_simulation_mode BOOLEAN NOT NULL, language_source SMALLINT NOT NULL DEFAULT 0)');
        $this->addSql('INSERT INTO project (id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode) SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode FROM __temp__project');
        $this->addSql('DROP TABLE __temp__project');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__project AS SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode FROM project');
        $this->addSql('DROP TABLE project');
        $this->addSql('CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, hosts CLOB DEFAULT NULL, public_key VARCHAR(64) NOT NULL, private_key CLOB NOT NULL, status INTEGER NOT NULL, spam_score DOUBLE PRECISION NOT NULL, statistic_storage_limit VARCHAR(7) NOT NULL, api_debug_mode BOOLEAN NOT NULL, verification_simulation_mode BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO project (id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode) SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, api_debug_mode, verification_simulation_mode FROM __temp__project');
        $this->addSql('DROP TABLE __temp__project');
    }
}

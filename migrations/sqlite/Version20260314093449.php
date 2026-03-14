<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260314093449 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project ADD COLUMN spam_data_returned BOOLEAN DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE project ADD COLUMN metadata_returned BOOLEAN DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__project AS SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, metadata_allowed, api_debug_mode, verification_simulation_mode, language_source, project_group_id FROM project');
        $this->addSql('DROP TABLE project');
        $this->addSql('CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, hosts CLOB DEFAULT NULL, public_key VARCHAR(64) NOT NULL, private_key CLOB NOT NULL, status SMALLINT NOT NULL, spam_score DOUBLE PRECISION NOT NULL, statistic_storage_limit VARCHAR(7) NOT NULL, metadata_allowed BOOLEAN DEFAULT 0 NOT NULL, api_debug_mode BOOLEAN NOT NULL, verification_simulation_mode BOOLEAN NOT NULL, language_source SMALLINT NOT NULL, project_group_id INTEGER DEFAULT NULL, CONSTRAINT FK_2FB3D0EEC31A529C FOREIGN KEY (project_group_id) REFERENCES project_group (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO project (id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, metadata_allowed, api_debug_mode, verification_simulation_mode, language_source, project_group_id) SELECT id, uuid, name, description, hosts, public_key, private_key, status, spam_score, statistic_storage_limit, metadata_allowed, api_debug_mode, verification_simulation_mode, language_source, project_group_id FROM __temp__project');
        $this->addSql('DROP TABLE __temp__project');
        $this->addSql('CREATE INDEX IDX_2FB3D0EEC31A529C ON project (project_group_id)');
        $this->addSql('CREATE INDEX p_publickey_idx ON project (public_key)');
    }
}

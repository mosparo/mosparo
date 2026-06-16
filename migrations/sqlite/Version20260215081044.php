<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215081044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE partial_submission (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, data CLOB NOT NULL, ignored_fields CLOB NOT NULL, updated_at DATETIME NOT NULL, submit_token_id INTEGER DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_81A9A7A12B4057C1 FOREIGN KEY (submit_token_id) REFERENCES submit_token (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_81A9A7A1166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_81A9A7A12B4057C1 ON partial_submission (submit_token_id)');
        $this->addSql('CREATE INDEX IDX_81A9A7A1166D1F9C ON partial_submission (project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE partial_submission');
    }
}

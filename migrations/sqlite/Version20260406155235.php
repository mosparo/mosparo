<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406155235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE detection_result (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, matched_field_rule_items CLOB NOT NULL, matched_submission_rules CLOB NOT NULL, submission_id INTEGER DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_9D26910FE1FD4933 FOREIGN KEY (submission_id) REFERENCES submission (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9D26910F166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9D26910FE1FD4933 ON detection_result (submission_id)');
        $this->addSql('CREATE INDEX IDX_9D26910F166D1F9C ON detection_result (project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE detection_result');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260317182918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE day_statistic ADD COLUMN number_of_delayed_requests INTEGER NOT NULL');
        $this->addSql('ALTER TABLE day_statistic ADD COLUMN number_of_blocked_requests INTEGER NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__day_statistic AS SELECT id, date, number_of_valid_submissions, number_of_spam_submissions, updated_at, project_id FROM day_statistic');
        $this->addSql('DROP TABLE day_statistic');
        $this->addSql('CREATE TABLE day_statistic (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, number_of_valid_submissions INTEGER NOT NULL, number_of_spam_submissions INTEGER NOT NULL, updated_at DATETIME DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_3D2B35AC166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO day_statistic (id, date, number_of_valid_submissions, number_of_spam_submissions, updated_at, project_id) SELECT id, date, number_of_valid_submissions, number_of_spam_submissions, updated_at, project_id FROM __temp__day_statistic');
        $this->addSql('DROP TABLE __temp__day_statistic');
        $this->addSql('CREATE INDEX IDX_3D2B35AC166D1F9C ON day_statistic (project_id)');
        $this->addSql('CREATE UNIQUE INDEX day_project_idx ON day_statistic (date, project_id)');
    }
}

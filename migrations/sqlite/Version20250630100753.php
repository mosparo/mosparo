<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250630100753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE submission ADD COLUMN issues CLOB DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__submission AS SELECT id, validation_token, data, signature, submitted_at, verified_at, matched_rule_items, ignored_fields, verified_fields, general_verifications, spam_rating, spam, spam_detection_rating, valid, submit_token_id, project_id FROM submission
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE submission
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE submission (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, validation_token VARCHAR(64) DEFAULT NULL, data CLOB NOT NULL, signature VARCHAR(64) DEFAULT NULL, submitted_at DATETIME NOT NULL, verified_at DATETIME DEFAULT NULL, matched_rule_items CLOB NOT NULL, ignored_fields CLOB NOT NULL, verified_fields CLOB NOT NULL, general_verifications CLOB NOT NULL, spam_rating DOUBLE PRECISION NOT NULL, spam BOOLEAN DEFAULT NULL, spam_detection_rating DOUBLE PRECISION NOT NULL, valid BOOLEAN DEFAULT NULL, submit_token_id INTEGER DEFAULT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_DB055AF32B4057C1 FOREIGN KEY (submit_token_id) REFERENCES submit_token (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DB055AF3166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO submission (id, validation_token, data, signature, submitted_at, verified_at, matched_rule_items, ignored_fields, verified_fields, general_verifications, spam_rating, spam, spam_detection_rating, valid, submit_token_id, project_id) SELECT id, validation_token, data, signature, submitted_at, verified_at, matched_rule_items, ignored_fields, verified_fields, general_verifications, spam_rating, spam, spam_detection_rating, valid, submit_token_id, project_id FROM __temp__submission
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__submission
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DB055AF32B4057C1 ON submission (submit_token_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DB055AF3166D1F9C ON submission (project_id)
        SQL);
    }
}

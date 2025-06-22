<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250609153012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__rule_item AS SELECT id, uuid, type, value, spam_rating_factor, rule_id, project_id FROM rule_item
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE rule_item
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE rule_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, type VARCHAR(255) NOT NULL, value CLOB NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, rule_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_4CDF7A69744E0351 FOREIGN KEY (rule_id) REFERENCES rule (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4CDF7A69166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO rule_item (id, uuid, type, value, spam_rating_factor, rule_id, project_id) SELECT id, uuid, type, value, spam_rating_factor, rule_id, project_id FROM __temp__rule_item
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__rule_item
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4CDF7A69166D1F9C ON rule_item (project_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4CDF7A69744E0351 ON rule_item (rule_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX uuid_idx ON rule_item (uuid)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__rule_item AS SELECT id, uuid, type, value, spam_rating_factor, rule_id, project_id FROM rule_item
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE rule_item
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE rule_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, type VARCHAR(255) NOT NULL, value CLOB NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, rule_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_4CDF7A69744E0351 FOREIGN KEY (rule_id) REFERENCES rule (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4CDF7A69166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO rule_item (id, uuid, type, value, spam_rating_factor, rule_id, project_id) SELECT id, uuid, type, value, spam_rating_factor, rule_id, project_id FROM __temp__rule_item
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__rule_item
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4CDF7A69744E0351 ON rule_item (rule_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4CDF7A69166D1F9C ON rule_item (project_id)
        SQL);
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250427173122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package ADD COLUMN uuid CHAR(36) NULL
        SQL);
        foreach ($this->connection->executeQuery('SELECT id FROM rule_package')->fetchAllAssociative() as $rulePackage) {
            $this->addSql('UPDATE rule_package SET uuid = :uuid WHERE id = :id', [
                'uuid' => Uuid::v4()->toRfc4122(),
                'id' => $rulePackage['id'],
            ]);
        }
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__rule_package AS SELECT id, uuid, type, name, source, spam_rating_factor, status, project_id FROM rule_package
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE rule_package
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE rule_package (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, type INTEGER DEFAULT 1 NOT NULL, name VARCHAR(255) NOT NULL, source CLOB DEFAULT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, status BOOLEAN NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_2EFC5E21166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO rule_package (id, uuid, type, name, source, spam_rating_factor, status, project_id) SELECT id, uuid, type, name, source, spam_rating_factor, status, project_id FROM __temp__rule_package
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__rule_package
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2EFC5E21166D1F9C ON rule_package (project_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__rule_package AS SELECT id, type, name, source, spam_rating_factor, status, project_id FROM rule_package
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE rule_package
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE rule_package (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type INTEGER DEFAULT 1 NOT NULL, name VARCHAR(255) NOT NULL, source CLOB DEFAULT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, status BOOLEAN NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_2EFC5E21166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO rule_package (id, type, name, source, spam_rating_factor, status, project_id) SELECT id, type, name, source, spam_rating_factor, status, project_id FROM __temp__rule_package
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__rule_package
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2EFC5E21166D1F9C ON rule_package (project_id)
        SQL);
    }
}

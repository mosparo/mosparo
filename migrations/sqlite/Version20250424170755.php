<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250424170755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE rule_package (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type INTEGER DEFAULT 1 NOT NULL, name VARCHAR(255) NOT NULL, source CLOB DEFAULT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, status BOOLEAN NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_2EFC5E21166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO rule_package (id, name, spam_rating_factor, status, source, project_id) SELECT id, name, spam_rating_factor, status, url, project_id FROM ruleset
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2EFC5E21166D1F9C ON rule_package (project_id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE rule_package_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, refreshed_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, refresh_interval INTEGER NOT NULL, rule_package_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_7ECFE2B7CFFD2724 FOREIGN KEY (rule_package_id) REFERENCES rule_package (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7ECFE2B7166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO rule_package_cache (id, refreshed_at, updated_at, refresh_interval, rule_package_id, project_id) SELECT id, refreshed_at, updated_at, refresh_interval, ruleset_id, project_id FROM ruleset_cache
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_7ECFE2B7CFFD2724 ON rule_package_cache (rule_package_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_7ECFE2B7166D1F9C ON rule_package_cache (project_id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE rule_package_rule_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, type VARCHAR(30) NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, rule_package_cache_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_90CF1D59C1C36394 FOREIGN KEY (rule_package_cache_id) REFERENCES rule_package_cache (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_90CF1D59166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO rule_package_rule_cache (id, uuid, name, description, type, spam_rating_factor, rule_package_cache_id, project_id) SELECT id, uuid, name, description, type, spam_rating_factor, ruleset_cache_id, project_id FROM ruleset_rule_cache
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_90CF1D59C1C36394 ON rule_package_rule_cache (rule_package_cache_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_90CF1D59166D1F9C ON rule_package_rule_cache (project_id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE rule_package_rule_item_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, type VARCHAR(255) NOT NULL, value CLOB NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, rule_package_rule_cache_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_FD468764F19B63A2 FOREIGN KEY (rule_package_rule_cache_id) REFERENCES rule_package_rule_cache (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_FD468764166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO rule_package_rule_item_cache (id, uuid, type, value, spam_rating_factor, rule_package_rule_cache_id, project_id) SELECT id, uuid, type, value, spam_rating_factor, ruleset_rule_cache_id, project_id FROM ruleset_rule_item_cache
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FD468764F19B63A2 ON rule_package_rule_item_cache (rule_package_rule_cache_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FD468764166D1F9C ON rule_package_rule_item_cache (project_id)
        SQL);

        $this->addSql(<<<'SQL'
            DROP TABLE ruleset
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ruleset_cache
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ruleset_rule_cache
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ruleset_rule_item_cache
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE ruleset (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE "BINARY", spam_rating_factor DOUBLE PRECISION DEFAULT NULL, status BOOLEAN NOT NULL, project_id INTEGER NOT NULL, url CLOB NOT NULL COLLATE "BINARY", CONSTRAINT FK_F41BE3BD166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO ruleset (id, name, spam_rating_factor, status, url, project_id) SELECT id, name, spam_rating_factor, status, source, project_id FROM rule_package
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F41BE3BD166D1F9C ON ruleset (project_id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE ruleset_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, refreshed_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, refresh_interval INTEGER NOT NULL, ruleset_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_378A314D54F1C144 FOREIGN KEY (ruleset_id) REFERENCES ruleset (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_378A314D166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO ruleset_cache (id, refreshed_at, updated_at, refresh_interval, ruleset_id, project_id) SELECT id, refreshed_at, updated_at, refresh_interval, rule_package_id, project_id FROM rule_package_cache
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_378A314D166D1F9C ON ruleset_cache (project_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_378A314D54F1C144 ON ruleset_cache (ruleset_id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE ruleset_rule_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL COLLATE "BINARY", name VARCHAR(255) NOT NULL COLLATE "BINARY", description CLOB DEFAULT NULL COLLATE "BINARY", type VARCHAR(30) NOT NULL COLLATE "BINARY", spam_rating_factor DOUBLE PRECISION DEFAULT NULL, ruleset_cache_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_F140F8B5D533B618 FOREIGN KEY (ruleset_cache_id) REFERENCES ruleset_cache (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F140F8B5166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO ruleset_rule_cache (id, uuid, name, description, type, spam_rating_factor, ruleset_cache_id, project_id) SELECT id, uuid, name, description, type, spam_rating_factor, rule_package_cache_id, project_id FROM rule_package_rule_cache
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F140F8B5166D1F9C ON ruleset_rule_cache (project_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F140F8B5D533B618 ON ruleset_rule_cache (ruleset_cache_id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE ruleset_rule_item_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL COLLATE "BINARY", type VARCHAR(255) NOT NULL COLLATE "BINARY", value CLOB NOT NULL COLLATE "BINARY", spam_rating_factor DOUBLE PRECISION DEFAULT NULL, ruleset_rule_cache_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_674C1AEBF6DD31F FOREIGN KEY (ruleset_rule_cache_id) REFERENCES ruleset_rule_cache (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_674C1AEB166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO ruleset_rule_item_cache (id, uuid, type, value, spam_rating_factor, ruleset_rule_cache_id, project_id) SELECT id, uuid, type, value, spam_rating_factor, rule_package_rule_cache_id, project_id FROM rule_package_rule_item_cache 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_674C1AEB166D1F9C ON ruleset_rule_item_cache (project_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_674C1AEBF6DD31F ON ruleset_rule_item_cache (ruleset_rule_cache_id)
        SQL);

        $this->addSql(<<<'SQL'
            DROP TABLE rule_package
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE rule_package_cache
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE rule_package_rule_cache
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE rule_package_rule_item_cache
        SQL);
    }
}

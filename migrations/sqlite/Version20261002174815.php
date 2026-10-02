<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002174815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__rule_item AS SELECT id, uuid, type, value, spam_rating_factor, prepared_value, hashed_value, rule_id, project_id FROM rule_item');
        $this->addSql('DROP TABLE rule_item');
        $this->addSql('CREATE TABLE rule_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, type VARCHAR(50) NOT NULL, value CLOB NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, prepared_value CLOB DEFAULT NULL, hashed_value VARCHAR(32) DEFAULT NULL, rule_id INTEGER NOT NULL, project_id INTEGER NOT NULL, preparation_version SMALLINT DEFAULT NULL, CONSTRAINT FK_4CDF7A69744E0351 FOREIGN KEY (rule_id) REFERENCES rule (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4CDF7A69166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO rule_item (id, uuid, type, value, spam_rating_factor, prepared_value, hashed_value, rule_id, project_id) SELECT id, uuid, type, value, spam_rating_factor, prepared_value, hashed_value, rule_id, project_id FROM __temp__rule_item');
        $this->addSql('DROP TABLE __temp__rule_item');
        $this->addSql('CREATE INDEX IDX_4CDF7A69166D1F9C ON rule_item (project_id)');
        $this->addSql('CREATE INDEX IDX_4CDF7A69744E0351 ON rule_item (rule_id)');
        $this->addSql('CREATE INDEX ri_hashed_idx ON rule_item (project_id, type, hashed_value)');
        $this->addSql('CREATE INDEX ri_uuid_idx ON rule_item (uuid)');
        $this->addSql('CREATE INDEX ri_pv_idx ON rule_item (preparation_version)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__rule_package_rule_item_cache AS SELECT id, uuid, type, value, spam_rating_factor, updated_at, prepared_value, hashed_value, rule_package_rule_cache_id, project_id FROM rule_package_rule_item_cache');
        $this->addSql('DROP TABLE rule_package_rule_item_cache');
        $this->addSql('CREATE TABLE rule_package_rule_item_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, type VARCHAR(50) NOT NULL, value CLOB NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, updated_at DATETIME DEFAULT NULL, prepared_value CLOB DEFAULT NULL, hashed_value VARCHAR(32) DEFAULT NULL, rule_package_rule_cache_id INTEGER NOT NULL, project_id INTEGER NOT NULL, preparation_version SMALLINT DEFAULT NULL, CONSTRAINT FK_FD468764F19B63A2 FOREIGN KEY (rule_package_rule_cache_id) REFERENCES rule_package_rule_cache (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_FD468764166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO rule_package_rule_item_cache (id, uuid, type, value, spam_rating_factor, updated_at, prepared_value, hashed_value, rule_package_rule_cache_id, project_id) SELECT id, uuid, type, value, spam_rating_factor, updated_at, prepared_value, hashed_value, rule_package_rule_cache_id, project_id FROM __temp__rule_package_rule_item_cache');
        $this->addSql('DROP TABLE __temp__rule_package_rule_item_cache');
        $this->addSql('CREATE INDEX IDX_FD468764166D1F9C ON rule_package_rule_item_cache (project_id)');
        $this->addSql('CREATE INDEX IDX_FD468764F19B63A2 ON rule_package_rule_item_cache (rule_package_rule_cache_id)');
        $this->addSql('CREATE INDEX rpric_rprc_project_idx ON rule_package_rule_item_cache (project_id, rule_package_rule_cache_id)');
        $this->addSql('CREATE INDEX rpric_hashed_idx ON rule_package_rule_item_cache (project_id, type, hashed_value)');
        $this->addSql('CREATE INDEX rpric_uuid_idx ON rule_package_rule_item_cache (uuid)');
        $this->addSql('CREATE INDEX rpric_pv_idx ON rule_package_rule_item_cache (preparation_version)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__rule_item AS SELECT id, uuid, type, value, spam_rating_factor, prepared_value, hashed_value, rule_id, project_id FROM rule_item');
        $this->addSql('DROP TABLE rule_item');
        $this->addSql('CREATE TABLE rule_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, type VARCHAR(50) NOT NULL, value CLOB NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, prepared_value CLOB DEFAULT NULL, hashed_value VARCHAR(32) DEFAULT NULL, rule_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_4CDF7A69744E0351 FOREIGN KEY (rule_id) REFERENCES rule (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4CDF7A69166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO rule_item (id, uuid, type, value, spam_rating_factor, prepared_value, hashed_value, rule_id, project_id) SELECT id, uuid, type, value, spam_rating_factor, prepared_value, hashed_value, rule_id, project_id FROM __temp__rule_item');
        $this->addSql('DROP TABLE __temp__rule_item');
        $this->addSql('CREATE INDEX ri_uuid_idx ON rule_item (uuid)');
        $this->addSql('CREATE INDEX ri_hashed_idx ON rule_item (project_id, type, hashed_value)');
        $this->addSql('CREATE INDEX IDX_4CDF7A69744E0351 ON rule_item (rule_id)');
        $this->addSql('CREATE INDEX IDX_4CDF7A69166D1F9C ON rule_item (project_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__rule_package_rule_item_cache AS SELECT id, uuid, type, value, spam_rating_factor, updated_at, prepared_value, hashed_value, rule_package_rule_cache_id, project_id FROM rule_package_rule_item_cache');
        $this->addSql('DROP TABLE rule_package_rule_item_cache');
        $this->addSql('CREATE TABLE rule_package_rule_item_cache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL, type VARCHAR(50) NOT NULL, value CLOB NOT NULL, spam_rating_factor DOUBLE PRECISION DEFAULT NULL, updated_at DATETIME DEFAULT NULL, prepared_value CLOB DEFAULT NULL, hashed_value VARCHAR(32) DEFAULT NULL, rule_package_rule_cache_id INTEGER NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_FD468764F19B63A2 FOREIGN KEY (rule_package_rule_cache_id) REFERENCES rule_package_rule_cache (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_FD468764166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO rule_package_rule_item_cache (id, uuid, type, value, spam_rating_factor, updated_at, prepared_value, hashed_value, rule_package_rule_cache_id, project_id) SELECT id, uuid, type, value, spam_rating_factor, updated_at, prepared_value, hashed_value, rule_package_rule_cache_id, project_id FROM __temp__rule_package_rule_item_cache');
        $this->addSql('DROP TABLE __temp__rule_package_rule_item_cache');
        $this->addSql('CREATE INDEX rpric_uuid_idx ON rule_package_rule_item_cache (uuid)');
        $this->addSql('CREATE INDEX rpric_hashed_idx ON rule_package_rule_item_cache (project_id, type, hashed_value)');
        $this->addSql('CREATE INDEX rpric_rprc_project_idx ON rule_package_rule_item_cache (project_id, rule_package_rule_cache_id)');
        $this->addSql('CREATE INDEX IDX_FD468764F19B63A2 ON rule_package_rule_item_cache (rule_package_rule_cache_id)');
        $this->addSql('CREATE INDEX IDX_FD468764166D1F9C ON rule_package_rule_item_cache (project_id)');
    }
}

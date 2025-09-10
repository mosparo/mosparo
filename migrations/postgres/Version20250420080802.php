<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250420080802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset RENAME TO rule_package
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset_cache RENAME TO rule_package_cache
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset_rule_cache RENAME TO rule_package_rule_cache
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset_rule_item_cache RENAME TO rule_package_rule_item_cache
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package ADD type INT DEFAULT 1 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package RENAME COLUMN url TO source
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package ALTER COLUMN source SET DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_cache RENAME COLUMN ruleset_id TO rule_package_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_cache RENAME COLUMN ruleset_cache_id TO rule_package_cache_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache RENAME COLUMN ruleset_rule_cache_id TO rule_package_rule_cache_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package RENAME TO ruleset
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_cache RENAME TO ruleset_cache
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_cache RENAME TO ruleset_rule_cache
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache RENAME TO ruleset_rule_item_cache
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset RENAME COLUMN source TO url
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset DROP type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset_cache RENAME COLUMN rule_package_id TO ruleset_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset_rule_cache RENAME COLUMN rule_package_cache_id TO ruleset_cache_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset_rule_item_cache RENAME COLUMN rule_package_rule_cache_id TO ruleset_rule_cache_id
        SQL);
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250420080757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            RENAME TABLE ruleset TO rule_package, ruleset_cache TO rule_package_cache, ruleset_rule_cache TO rule_package_rule_cache, ruleset_rule_item_cache TO rule_package_rule_item_cache
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package ADD type INT DEFAULT 1 NOT NULL, CHANGE url source LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_cache CHANGE ruleset_id rule_package_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_cache CHANGE ruleset_cache_id rule_package_cache_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache CHANGE ruleset_rule_cache_id rule_package_rule_cache_id INT NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            RENAME TABLE rule_package TO ruleset, rule_package_cache TO ruleset_cache, rule_package_rule_cache TO ruleset_rule_cache, rule_package_rule_item_cache TO ruleset_rule_item_cache
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset CHANGE source url LONGTEXT NULL, DROP type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset_cache CHANGE rule_package_id ruleset_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset_rule_cache CHANGE rule_package_cache_id ruleset_cache_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ruleset_rule_item_cache CHANGE rule_package_rule_cache_id ruleset_rule_cache_id INT NOT NULL
        SQL);
    }
}

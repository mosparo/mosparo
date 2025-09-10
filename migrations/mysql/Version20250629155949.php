<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250629155949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_item ADD prepared_value LONGTEXT DEFAULT NULL, ADD hashed_value VARCHAR(32) DEFAULT NULL, CHANGE type type VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX ri_uuid_idx ON rule_item (uuid)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX ri_hashed_idx ON rule_item (project_id, type, hashed_value)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX rprc_uuid_idx ON rule_package_rule_cache (uuid)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache ADD prepared_value LONGTEXT DEFAULT NULL, ADD hashed_value VARCHAR(32) DEFAULT NULL, CHANGE type type VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX rpric_uuid_idx ON rule_package_rule_item_cache (uuid)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX rpric_hashed_idx ON rule_package_rule_item_cache (project_id, type, hashed_value)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX rpric_uuid_idx ON rule_package_rule_item_cache
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX rpric_hashed_idx ON rule_package_rule_item_cache
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache DROP prepared_value, DROP hashed_value, CHANGE type type VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX rprc_uuid_idx ON rule_package_rule_cache
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX ri_uuid_idx ON rule_item
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX ri_hashed_idx ON rule_item
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_item DROP prepared_value, DROP hashed_value, CHANGE type type VARCHAR(255) NOT NULL
        SQL);
    }
}

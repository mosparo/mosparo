<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250629155220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_item ADD prepared_value TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_item ADD hashed_value VARCHAR(32) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_item ALTER type TYPE VARCHAR(50)
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
            ALTER TABLE rule_package_rule_item_cache ADD prepared_value TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache ADD hashed_value VARCHAR(32) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache ALTER type TYPE VARCHAR(50)
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
            DROP INDEX ri_uuid_idx
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX ri_hashed_idx
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_item DROP prepared_value
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_item DROP hashed_value
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_item ALTER type TYPE VARCHAR(255)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX rpric_uuid_idx
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX rpric_hashed_idx
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache DROP prepared_value
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache DROP hashed_value
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache ALTER type TYPE VARCHAR(255)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX rprc_uuid_idx
        SQL);
    }
}

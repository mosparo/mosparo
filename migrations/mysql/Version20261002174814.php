<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002174814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rule_item ADD preparation_version SMALLINT DEFAULT NULL');
        $this->addSql('CREATE INDEX ri_pv_idx ON rule_item (preparation_version)');
        $this->addSql('ALTER TABLE rule_package_rule_item_cache ADD preparation_version SMALLINT DEFAULT NULL');
        $this->addSql('CREATE INDEX rpric_pv_idx ON rule_package_rule_item_cache (preparation_version)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX rpric_pv_idx ON rule_package_rule_item_cache');
        $this->addSql('ALTER TABLE rule_package_rule_item_cache DROP preparation_version');
        $this->addSql('DROP INDEX ri_pv_idx ON rule_item');
        $this->addSql('ALTER TABLE rule_item DROP preparation_version');
    }
}

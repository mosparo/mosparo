<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250427170020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package ADD uuid CHAR(36) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE rule_package SET uuid = UUID()
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package RENAME INDEX idx_f41be3bd166d1f9c TO IDX_2EFC5E21166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_cache RENAME INDEX uniq_378a314d54f1c144 TO UNIQ_7ECFE2B7CFFD2724
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_cache RENAME INDEX idx_378a314d166d1f9c TO IDX_7ECFE2B7166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_cache RENAME INDEX idx_f140f8b5d533b618 TO IDX_90CF1D59C1C36394
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_cache RENAME INDEX idx_f140f8b5166d1f9c TO IDX_90CF1D59166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache RENAME INDEX idx_674c1aebf6dd31f TO IDX_FD468764F19B63A2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache RENAME INDEX idx_674c1aeb166d1f9c TO IDX_FD468764166D1F9C
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache RENAME INDEX idx_fd468764f19b63a2 TO IDX_674C1AEBF6DD31F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_item_cache RENAME INDEX idx_fd468764166d1f9c TO IDX_674C1AEB166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_cache RENAME INDEX idx_90cf1d59c1c36394 TO IDX_F140F8B5D533B618
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_rule_cache RENAME INDEX idx_90cf1d59166d1f9c TO IDX_F140F8B5166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_cache RENAME INDEX idx_7ecfe2b7166d1f9c TO IDX_378A314D166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package_cache RENAME INDEX uniq_7ecfe2b7cffd2724 TO UNIQ_378A314D54F1C144
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package DROP uuid
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package RENAME INDEX idx_2efc5e21166d1f9c TO IDX_F41BE3BD166D1F9C
        SQL);
    }
}

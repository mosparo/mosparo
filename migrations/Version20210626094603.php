<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210626094603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ruleset_cache (id INT AUTO_INCREMENT NOT NULL, ruleset_id INT NOT NULL, project_id INT NOT NULL, refreshed_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, refresh_interval INT NOT NULL, UNIQUE INDEX UNIQ_378A314D54F1C144 (ruleset_id), INDEX IDX_378A314D166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ruleset_rule_cache (id INT AUTO_INCREMENT NOT NULL, ruleset_cache_id INT NOT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(30) NOT NULL, items LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', spam_rating_factor DOUBLE PRECISION DEFAULT NULL, INDEX IDX_F140F8B5D533B618 (ruleset_cache_id), INDEX IDX_F140F8B5166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ruleset_cache ADD CONSTRAINT FK_378A314D54F1C144 FOREIGN KEY (ruleset_id) REFERENCES ruleset (id)');
        $this->addSql('ALTER TABLE ruleset_cache ADD CONSTRAINT FK_378A314D166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE ruleset_rule_cache ADD CONSTRAINT FK_F140F8B5D533B618 FOREIGN KEY (ruleset_cache_id) REFERENCES ruleset_cache (id)');
        $this->addSql('ALTER TABLE ruleset_rule_cache ADD CONSTRAINT FK_F140F8B5166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE ruleset DROP refreshed_at, DROP updated_at, DROP refresh_interval');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ruleset_rule_cache DROP FOREIGN KEY FK_F140F8B5D533B618');
        $this->addSql('DROP TABLE ruleset_cache');
        $this->addSql('DROP TABLE ruleset_rule_cache');
        $this->addSql('ALTER TABLE ruleset ADD refreshed_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD refresh_interval INT DEFAULT NULL');
    }
}

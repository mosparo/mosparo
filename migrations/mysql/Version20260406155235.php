<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406155235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE detection_result (id INT AUTO_INCREMENT NOT NULL, matched_field_rule_items JSON NOT NULL, matched_submission_rules JSON NOT NULL, submission_id INT DEFAULT NULL, project_id INT NOT NULL, UNIQUE INDEX UNIQ_9D26910FE1FD4933 (submission_id), INDEX IDX_9D26910F166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE detection_result ADD CONSTRAINT FK_9D26910FE1FD4933 FOREIGN KEY (submission_id) REFERENCES submission (id)');
        $this->addSql('ALTER TABLE detection_result ADD CONSTRAINT FK_9D26910F166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detection_result DROP FOREIGN KEY FK_9D26910FE1FD4933');
        $this->addSql('ALTER TABLE detection_result DROP FOREIGN KEY FK_9D26910F166D1F9C');
        $this->addSql('DROP TABLE detection_result');
    }
}

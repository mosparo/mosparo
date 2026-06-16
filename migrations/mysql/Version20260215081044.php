<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215081044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE partial_submission (id INT AUTO_INCREMENT NOT NULL, data LONGTEXT NOT NULL, ignored_fields JSON NOT NULL, updated_at DATETIME NOT NULL, submit_token_id INT DEFAULT NULL, project_id INT NOT NULL, UNIQUE INDEX UNIQ_81A9A7A12B4057C1 (submit_token_id), INDEX IDX_81A9A7A1166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE partial_submission ADD CONSTRAINT FK_81A9A7A12B4057C1 FOREIGN KEY (submit_token_id) REFERENCES submit_token (id)');
        $this->addSql('ALTER TABLE partial_submission ADD CONSTRAINT FK_81A9A7A1166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE partial_submission DROP FOREIGN KEY FK_81A9A7A12B4057C1');
        $this->addSql('ALTER TABLE partial_submission DROP FOREIGN KEY FK_81A9A7A1166D1F9C');
        $this->addSql('DROP TABLE partial_submission');
    }
}

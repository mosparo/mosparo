<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210621174725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE submission (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', signature VARCHAR(40) NOT NULL, ip_address VARCHAR(255) NOT NULL, submitted_at DATETIME NOT NULL, matched_rule_items LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', spam_rating DOUBLE PRECISION NOT NULL, INDEX IDX_DB055AF3166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE submit_token (id INT AUTO_INCREMENT NOT NULL, submission_id INT DEFAULT NULL, project_id INT NOT NULL, ip_address VARCHAR(255) NOT NULL, signature VARCHAR(40) NOT NULL, token VARCHAR(40) NOT NULL, created_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, valid_until DATETIME NOT NULL, UNIQUE INDEX UNIQ_6C047AC8E1FD4933 (submission_id), INDEX IDX_6C047AC8166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF3166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE submit_token ADD CONSTRAINT FK_6C047AC8E1FD4933 FOREIGN KEY (submission_id) REFERENCES submission (id)');
        $this->addSql('ALTER TABLE submit_token ADD CONSTRAINT FK_6C047AC8166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE submit_token DROP FOREIGN KEY FK_6C047AC8E1FD4933');
        $this->addSql('DROP TABLE submission');
        $this->addSql('DROP TABLE submit_token');
    }
}

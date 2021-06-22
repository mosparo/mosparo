<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210619160600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rule ADD project_id INT NOT NULL');
        $this->addSql('ALTER TABLE rule ADD CONSTRAINT FK_46D8ACCC166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('CREATE INDEX IDX_46D8ACCC166D1F9C ON rule (project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rule DROP FOREIGN KEY FK_46D8ACCC166D1F9C');
        $this->addSql('DROP INDEX IDX_46D8ACCC166D1F9C ON rule');
        $this->addSql('ALTER TABLE rule DROP project_id');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240922173821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_group (id INT AUTO_INCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, parent_id INT DEFAULT NULL, INDEX IDX_7E954D5B727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_group ADD CONSTRAINT FK_7E954D5B727ACA70 FOREIGN KEY (parent_id) REFERENCES project_group (id)');
        $this->addSql('ALTER TABLE project ADD project_group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEC31A529C FOREIGN KEY (project_group_id) REFERENCES project_group (id)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EEC31A529C ON project (project_group_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_group DROP FOREIGN KEY FK_7E954D5B727ACA70');
        $this->addSql('DROP TABLE project_group');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEC31A529C');
        $this->addSql('DROP INDEX IDX_2FB3D0EEC31A529C ON project');
        $this->addSql('ALTER TABLE project DROP project_group_id');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220616092619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_config_value (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, `name` VARCHAR(255) NOT NULL, value LONGTEXT DEFAULT NULL, INDEX IDX_8AFDEE17166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_config_value ADD CONSTRAINT FK_8AFDEE17166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project DROP config_values');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE project_config_value');
        $this->addSql('ALTER TABLE project ADD config_values LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231122143647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'V1.1: Add the security guidelines';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE security_guideline (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, priority INT NOT NULL, subnets JSON NOT NULL COMMENT \'(DC2Type:json)\', country_codes JSON NOT NULL COMMENT \'(DC2Type:json)\', as_numbers JSON NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_7ECC68E5166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE security_guideline_config_value (id INT AUTO_INCREMENT NOT NULL, security_guideline_id INT NOT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, value JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_24DA35EDF69EB831 (security_guideline_id), INDEX IDX_24DA35ED166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE security_guideline ADD CONSTRAINT FK_7ECC68E5166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE security_guideline_config_value ADD CONSTRAINT FK_24DA35EDF69EB831 FOREIGN KEY (security_guideline_id) REFERENCES security_guideline (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE security_guideline_config_value ADD CONSTRAINT FK_24DA35ED166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE security_guideline DROP FOREIGN KEY FK_7ECC68E5166D1F9C');
        $this->addSql('ALTER TABLE security_guideline_config_value DROP FOREIGN KEY FK_24DA35EDF69EB831');
        $this->addSql('ALTER TABLE security_guideline_config_value DROP FOREIGN KEY FK_24DA35ED166D1F9C');
        $this->addSql('DROP TABLE security_guideline');
        $this->addSql('DROP TABLE security_guideline_config_value');
    }
}

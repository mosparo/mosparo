<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230521093341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_config_value DROP FOREIGN KEY FK_8AFDEE17166D1F9C');
        $this->addSql('ALTER TABLE project_config_value ADD CONSTRAINT FK_8AFDEE17166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_config_value DROP FOREIGN KEY FK_8AFDEE17166D1F9C');
        $this->addSql('ALTER TABLE project_config_value ADD CONSTRAINT FK_8AFDEE17166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
    }
}

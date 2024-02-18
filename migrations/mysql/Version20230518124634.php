<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230518124634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the designMode config value for all existing projects.';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->connection->executeQuery('SELECT id FROM project')->fetchAllAssociative() as $project) {
            $this->addSql('INSERT INTO project_config_value (name, value, project_id) VALUES (:name, :value, :projectId)', [
                'name' => 'designMode',
                'value' => serialize('advanced'),
                'projectId' => $project['id'],
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        // No down functionality available since the additional designMode value is acceptable.
    }
}

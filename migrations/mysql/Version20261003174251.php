<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261003174251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Remove duplicated project memberships before adding the unique index. We keep the membership with
        // the lowest ID since this is the membership mosparo used until now (see Project::getProjectMember()).
        // The additional subquery is required for MySQL, which does not allow selecting from the table
        // that is modified in the same query.
        $this->addSql('DELETE FROM project_member WHERE id NOT IN (SELECT id FROM (SELECT MIN(id) AS id FROM project_member GROUP BY user_id, project_id) AS keep_ids)');
        $this->addSql('CREATE UNIQUE INDEX project_user_idx ON project_member (user_id, project_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX project_user_idx ON project_member');
    }
}

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
        /**
         * This migration is a bit more complex than others. This has a simple reason. When testing version 1.4,
         * we've detected an issue on one test hosting. The hosting is a shared standard web hosting. It was not possible
         * to finish the update. We've started to investigate the issue and found it in this migration.
         *
         * The reason for this issue is that we did not specify the table engine in the original version of the migration.
         * Because of that, the table `project_group` was created with the default table engine of the database server.
         * This was InnoDB in all of our other tests, but not on this shared web hosting. Because of this, the table
         * was created with MyISAM instead of InnoDB.
         *
         * It's not possible to use foreign keys between InnoDB and MyISAM tables, so the migration failed when executing
         * it. To fix this issue, we have to adjust the engine for the project_group table.
         *
         * Additionally, we must execute the required steps to ensure everything is done correctly. We cannot add
         * a new migration for this, because this one (wrong) migration would always be executed again (with every update).
         *
         * Steps:
         * 1. Create the table project_group or alter the engine, if the table already exists
         * 2. Add the foreign key for the parent project group
         * 3. Add the column project_group_id to the project table
         * 4. Add the foreign key for the project_group_id in the project table
         */
        $hasParentFk = false;
        if (!$schema->hasTable('project_group')) {
            $this->addSql('CREATE TABLE project_group (id INT AUTO_INCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, parent_id INT DEFAULT NULL, INDEX IDX_7E954D5B727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        } else {
            $projectGroupTable = $schema->getTable('project_group');

            if (strtolower($projectGroupTable->getOption('engine')) !== 'innodb') {
                $this->addSql('ALTER TABLE project_group ENGINE=InnoDB');
            }

            $hasParentFk = $projectGroupTable->hasForeignKey('FK_7E954D5B727ACA70');
        }

        if (!$hasParentFk) {
            $this->addSql('ALTER TABLE project_group ADD CONSTRAINT FK_7E954D5B727ACA70 FOREIGN KEY (parent_id) REFERENCES project_group (id)');
        }

        $projectTable = $schema->getTable('project');

        if (!$projectTable->hasColumn('project_group_id')) {
            $this->addSql('ALTER TABLE project ADD project_group_id INT DEFAULT NULL');
        }

        if (!$projectTable->hasForeignKey('FK_2FB3D0EEC31A529C')) {
            $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEC31A529C FOREIGN KEY (project_group_id) REFERENCES project_group (id)');
            $this->addSql('CREATE INDEX IDX_2FB3D0EEC31A529C ON project (project_group_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEC31A529C');
        $this->addSql('DROP INDEX IDX_2FB3D0EEC31A529C ON project');
        $this->addSql('ALTER TABLE project DROP project_group_id');
        $this->addSql('ALTER TABLE project_group DROP FOREIGN KEY FK_7E954D5B727ACA70');
        $this->addSql('DROP TABLE project_group');
    }
}

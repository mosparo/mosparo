<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261003174252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__project_member AS SELECT id, role, project_id, user_id FROM project_member');
        $this->addSql('DROP TABLE project_member');
        $this->addSql('CREATE TABLE project_member (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, role VARCHAR(30) NOT NULL, project_id INTEGER NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_67401132166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_67401132A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO project_member (id, role, project_id, user_id) SELECT id, role, project_id, user_id FROM __temp__project_member');
        $this->addSql('DROP TABLE __temp__project_member');
        $this->addSql('CREATE INDEX IDX_67401132A76ED395 ON project_member (user_id)');
        $this->addSql('CREATE INDEX IDX_67401132166D1F9C ON project_member (project_id)');
        $this->addSql('CREATE UNIQUE INDEX project_user_idx ON project_member (user_id, project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__project_member AS SELECT id, role, project_id, user_id FROM project_member');
        $this->addSql('DROP TABLE project_member');
        $this->addSql('CREATE TABLE project_member (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, role VARCHAR(30) NOT NULL, project_id INTEGER NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_67401132166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_67401132A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO project_member (id, role, project_id, user_id) SELECT id, role, project_id, user_id FROM __temp__project_member');
        $this->addSql('DROP TABLE __temp__project_member');
        $this->addSql('CREATE INDEX IDX_67401132166D1F9C ON project_member (project_id)');
        $this->addSql('CREATE INDEX IDX_67401132A76ED395 ON project_member (user_id)');
    }
}

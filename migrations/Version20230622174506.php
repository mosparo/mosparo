<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230622174506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE submit_token DROP FOREIGN KEY FK_6C047AC8E1FD4933');
        $this->addSql('DROP INDEX UNIQ_6C047AC8E1FD4933 ON submit_token');
        $this->addSql('ALTER TABLE submit_token CHANGE submission_id valid_submission_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE submit_token ADD CONSTRAINT FK_6C047AC86210F1A7 FOREIGN KEY (valid_submission_id) REFERENCES submission (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6C047AC86210F1A7 ON submit_token (valid_submission_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE submit_token DROP FOREIGN KEY FK_6C047AC86210F1A7');
        $this->addSql('DROP INDEX UNIQ_6C047AC86210F1A7 ON submit_token');
        $this->addSql('ALTER TABLE submit_token CHANGE valid_submission_id submission_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE submit_token ADD CONSTRAINT FK_6C047AC8E1FD4933 FOREIGN KEY (submission_id) REFERENCES submission (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6C047AC8E1FD4933 ON submit_token (submission_id)');
    }
}

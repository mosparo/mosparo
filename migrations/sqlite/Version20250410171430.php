<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410171430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE cleanup_statistic (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date_time DATETIME NOT NULL, cleanup_executor INTEGER NOT NULL, number_of_stored_submit_tokens INTEGER NOT NULL, number_of_deleted_submit_tokens INTEGER NOT NULL, number_of_stored_submissions INTEGER NOT NULL, number_of_deleted_submissions INTEGER NOT NULL, execution_time DOUBLE PRECISION NOT NULL, cleanup_status INTEGER NOT NULL)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE cleanup_statistic
        SQL);
    }
}

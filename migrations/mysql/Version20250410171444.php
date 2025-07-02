<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410171444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE cleanup_statistic (id INT AUTO_INCREMENT NOT NULL, date_time DATETIME NOT NULL, cleanup_executor INT NOT NULL, number_of_stored_submit_tokens INT NOT NULL, number_of_deleted_submit_tokens INT NOT NULL, number_of_stored_submissions INT NOT NULL, number_of_deleted_submissions INT NOT NULL, execution_time DOUBLE PRECISION NOT NULL, cleanup_status INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
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

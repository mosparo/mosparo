<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117111237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE security_guideline ADD form_page_urls JSON NOT NULL');
        $this->addSql('ALTER TABLE security_guideline ADD form_action_urls JSON NOT NULL');
        $this->addSql('ALTER TABLE security_guideline ADD form_ids JSON NOT NULL');
        $this->addSql('ALTER TABLE submit_token ADD form_action_url TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE submit_token ADD form_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE submit_token DROP form_action_url');
        $this->addSql('ALTER TABLE submit_token DROP form_id');
        $this->addSql('ALTER TABLE security_guideline DROP form_page_urls');
        $this->addSql('ALTER TABLE security_guideline DROP form_action_urls');
        $this->addSql('ALTER TABLE security_guideline DROP form_ids');
    }
}

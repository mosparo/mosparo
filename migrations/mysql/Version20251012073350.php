<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251012073350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX cs_datetime_idx ON cleanup_statistic (date_time)');
        $this->addSql('CREATE INDEX d_validuntil_idx ON delay (valid_until)');
        $this->addSql('CREATE INDEX ip_ipaddress_idx ON ip_localization (ip_address)');
        $this->addSql('CREATE INDEX l_validuntil_idx ON lockout (valid_until)');
        $this->addSql('CREATE INDEX p_publickey_idx ON project (public_key)');
        $this->addSql('CREATE INDEX s_submittedat_idx ON submission (submitted_at)');
        $this->addSql('CREATE INDEX s_spam_idx ON submission (spam)');
        $this->addSql('CREATE INDEX s_valid_idx ON submission (valid)');
        $this->addSql('CREATE INDEX st_token_idx ON submit_token (token)');
        $this->addSql('CREATE INDEX st_createdat_idx ON submit_token (created_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX s_submittedat_idx ON submission');
        $this->addSql('DROP INDEX s_spam_idx ON submission');
        $this->addSql('DROP INDEX s_valid_idx ON submission');
        $this->addSql('DROP INDEX st_token_idx ON submit_token');
        $this->addSql('DROP INDEX st_createdat_idx ON submit_token');
        $this->addSql('DROP INDEX cs_datetime_idx ON cleanup_statistic');
        $this->addSql('DROP INDEX ip_ipaddress_idx ON ip_localization');
        $this->addSql('DROP INDEX d_validuntil_idx ON delay');
        $this->addSql('DROP INDEX l_validuntil_idx ON lockout');
        $this->addSql('DROP INDEX p_publickey_idx ON project');
    }
}

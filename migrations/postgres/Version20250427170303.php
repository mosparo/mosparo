<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250427170303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package ADD uuid UUID NULL
        SQL);
        foreach ($this->connection->executeQuery('SELECT id FROM rule_package')->fetchAllAssociative() as $rulePackage) {
            $this->addSql('UPDATE rule_package SET uuid = :uuid WHERE id = :id', [
                'uuid' => Uuid::v4()->toRfc4122(),
                'id' => $rulePackage['id'],
            ]);
        }
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package ALTER uuid SET NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_f41be3bd166d1f9c RENAME TO IDX_2EFC5E21166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX uniq_378a314d54f1c144 RENAME TO UNIQ_7ECFE2B7CFFD2724
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_378a314d166d1f9c RENAME TO IDX_7ECFE2B7166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_f140f8b5d533b618 RENAME TO IDX_90CF1D59C1C36394
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_f140f8b5166d1f9c RENAME TO IDX_90CF1D59166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_674c1aebf6dd31f RENAME TO IDX_FD468764F19B63A2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_674c1aeb166d1f9c RENAME TO IDX_FD468764166D1F9C
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_7ecfe2b7166d1f9c RENAME TO idx_378a314d166d1f9c
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX uniq_7ecfe2b7cffd2724 RENAME TO uniq_378a314d54f1c144
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_90cf1d59166d1f9c RENAME TO idx_f140f8b5166d1f9c
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_90cf1d59c1c36394 RENAME TO idx_f140f8b5d533b618
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_fd468764166d1f9c RENAME TO idx_674c1aeb166d1f9c
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_fd468764f19b63a2 RENAME TO idx_674c1aebf6dd31f
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rule_package DROP uuid
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_2efc5e21166d1f9c RENAME TO idx_f41be3bd166d1f9c
        SQL);
    }
}

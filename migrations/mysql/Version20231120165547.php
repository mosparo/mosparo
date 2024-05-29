<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Mosparo\Util\DateRangeUtil;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231120165547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Create the table
        $this->addSql('CREATE TABLE day_statistic (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, date DATE NOT NULL, number_of_valid_submissions INT NOT NULL, number_of_spam_submissions INT NOT NULL, INDEX IDX_3D2B35AC166D1F9C (project_id), UNIQUE INDEX day_project_idx (date, project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE day_statistic ADD CONSTRAINT FK_3D2B35AC166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');

        // Add the option
        $this->addSql('ALTER TABLE project ADD statistic_storage_limit VARCHAR(7) NOT NULL');

        // Write the day statistic for the existing submissions
        $statement = $this->connection->prepare('SELECT s.submitted_at, s.valid, s.spam FROM submission s WHERE (s.spam = 1 OR s.valid IS NOT NULL) AND s.project_id = ?');
        foreach ($this->connection->executeQuery('SELECT id FROM project')->fetchAllAssociative() as $project) {
            $statement->bindValue(1, $project['id'], ParameterType::INTEGER);

            $data = [];
            foreach ($statement->executeQuery()->fetchAllAssociative() as $submissionData) {
                $type = ($submissionData['spam'] || !$submissionData['valid']) ? 'numberOfSpamSubmissions' : 'numberOfValidSubmissions';

                $day = (new \DateTime($submissionData['submitted_at']))->format('Y-m-d');
                if (!isset($data[$day])) {
                    $data[$day] = ['numberOfValidSubmissions' => 0, 'numberOfSpamSubmissions' => 0];
                }

                $data[$day][$type]++;
            }

            foreach ($data as $day => $values) {
                $this->addSql('INSERT INTO day_statistic (date, number_of_valid_submissions, number_of_spam_submissions, project_id) VALUES (:date, :numberOfValidSubmissions, :numberOfSpamSubmissions, :projectId)', [
                    'date' => $day,
                    'numberOfValidSubmissions' => $values['numberOfValidSubmissions'],
                    'numberOfSpamSubmissions' => $values['numberOfSpamSubmissions'],
                    'projectId' => $project['id'],
                ]);
            }

            $this->addSql('UPDATE project SET statistic_storage_limit = :forever', [
                'forever' => DateRangeUtil::DATE_RANGE_FOREVER,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        // Delete the table
        $this->addSql('ALTER TABLE day_statistic DROP FOREIGN KEY FK_3D2B35AC166D1F9C');
        $this->addSql('DROP TABLE day_statistic');

        // Remove the option
        $this->addSql('ALTER TABLE project DROP statistic_storage_limit');
    }
}

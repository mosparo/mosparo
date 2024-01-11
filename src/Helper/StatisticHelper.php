<?php

namespace Mosparo\Helper;

use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\DayStatistic;
use Mosparo\Entity\Submission;

class StatisticHelper
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Returns the statistical data for the stored submissions of the active project.
     * The method will return the data in the structure for the Statistic API.
     *
     * @param \DateTime|null $startDate
     * @return array
     */
    public function getStatisticData(DateTime $startDate = null): array
    {
        $builder = $this->entityManager->createQueryBuilder();
        $builder
            ->select('ds')
            ->from(DayStatistic::class, 'ds')
            ->orderBy('ds.date', 'ASC');

        if ($startDate) {
            $builder
                ->where('ds.date >= :startDate')
                ->setParameter(':startDate', $startDate->format('Y-m-d'));
        }

        $data = ['numberOfValidSubmissions' => 0, 'numberOfSpamSubmissions' => 0, 'numbersByDate' => []];
        foreach ($builder->getQuery()->getResult() as $dayStatistic) {
            $data['numberOfValidSubmissions'] += $dayStatistic->getNumberOfValidSubmissions();
            $data['numberOfSpamSubmissions'] += $dayStatistic->getNumberOfSpamSubmissions();

            $day = $dayStatistic->getDate()->format('Y-m-d');
            $data['numbersByDate'][$day] = [
                'numberOfValidSubmissions' => $dayStatistic->getNumberOfValidSubmissions(),
                'numberOfSpamSubmissions' => $dayStatistic->getNumberOfSpamSubmissions(),
            ];
        };

        return $data;
    }

    /**
     * Increases the number of valid or spam submissions for the day of the submission.
     *
     * @param \Mosparo\Entity\Submission $submission
     * @param bool $create
     * @return bool
     */
    public function increaseDayStatistic(Submission $submission, $create = true): bool
    {
        // We try to increase the number per SQL query to be as efficient as possible.
        // This query will fail for the first time on a day because no row exists for
        // the current day. But this case will be handled by the extra functionality.
        // With this approach, we minimize the load on the database.
        $builder = $this->entityManager->createQueryBuilder();
        $builder
            ->update(DayStatistic::class, 'ds');

        if ($submission->isValid() && !$submission->isSpam()) {
            $builder
                ->set('ds.numberOfValidSubmissions', 'ds.numberOfValidSubmissions + 1');
        } else {
            $builder
                ->set('ds.numberOfSpamSubmissions', 'ds.numberOfSpamSubmissions + 1');
        }

        $builder
            ->where('ds.date = :date')
            ->andWhere('ds.project = :project')
            ->setParameter('date', (new DateTime())->format('Y-m-d'))
            ->setParameter('project', $submission->getProject());

        $query = $builder->getQuery();
        $res = $query->execute();
        $result = ($res > 0);

        // If no DayStatistic object for the active project and date exists, create one
        if (!$result && $create) {
            $result = $this->createDayStatistic($submission);

            // If we get false back, it means that the DayStatistic element for this day already exists,
            // probably because another request added it, and now we try again to increase the statistic.
            if ($result === false) {
                $result = $this->increaseDayStatistic($submission, false);
            }
        }

        return $result;
    }

    /**
     * Create a DayStatistic object for the given Submission object.
     * Returns true if everything worked correctly or false, if the
     * DayStatistic object already exists for the given day and project.
     *
     * @param Submission $submission
     * @return bool
     *
     * @throws \Exception
     */
    protected function createDayStatistic(Submission $submission): bool
    {
        $dayStatistic = new DayStatistic();
        $dayStatistic->setProject($submission->getProject());

        if ($submission->isValid() && !$submission->isSpam()) {
            $dayStatistic->setNumberOfValidSubmissions(1);
        } else {
            $dayStatistic->setNumberOfSpamSubmissions(1);
        }

        try {
            $this->entityManager->persist($dayStatistic);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            return false;
        } catch (\Exception $e) {
            throw $e;
        }

        return true;
    }
}
<?php

namespace Mosparo\Helper;

use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\DayStatistic;
use Mosparo\Entity\Project;
use Mosparo\Entity\Submission;
use Mosparo\Enum\IncreaseReason;
use Mosparo\Util\DateRangeUtil;

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

        $data = ['numberOfValidSubmissions' => 0, 'numberOfSpamSubmissions' => 0, 'numberOfDelayedRequests' => 0, 'numberOfBlockedRequests' => 0, 'numbersByDate' => []];
        foreach ($builder->getQuery()->getResult() as $dayStatistic) {
            $data['numberOfValidSubmissions'] += $dayStatistic->getNumberOfValidSubmissions();
            $data['numberOfSpamSubmissions'] += $dayStatistic->getNumberOfSpamSubmissions();
            $data['numberOfDelayedRequests'] += $dayStatistic->getNumberOfDelayedRequests();
            $data['numberOfBlockedRequests'] += $dayStatistic->getNumberOfBlockedRequests();

            $day = $dayStatistic->getDate()->format('Y-m-d');
            $data['numbersByDate'][$day] = [
                'numberOfValidSubmissions' => $dayStatistic->getNumberOfValidSubmissions(),
                'numberOfSpamSubmissions' => $dayStatistic->getNumberOfSpamSubmissions(),
                'numberOfDelayedRequests' => $dayStatistic->getNumberOfDelayedRequests(),
                'numberOfBlockedRequests' => $dayStatistic->getNumberOfBlockedRequests(),

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
    public function increaseDayStatisticForSubmission(Submission $submission, $create = true): bool
    {
        $reason = IncreaseReason::SPAM;
        if ($submission->isValid() && !$submission->isSpam()) {
            $reason = IncreaseReason::VALID;
        }

        return $this->increaseDayStatistic($reason, $submission->getProject(), $create);
    }

    /**
     * Increases the number in the DayStatistics of today for the given reason and project.
     *
     * @param IncreaseReason $reason
     * @param Project $project
     * @param bool $create
     * @return bool
     */
    public function increaseDayStatistic(IncreaseReason $reason, Project $project, bool $create = true): bool
    {
        // We try to increase the number per SQL query to be as efficient as possible.
        // This query will fail for the first time on a day because no row exists for
        // the current day. But this case will be handled by the extra functionality.
        // With this approach, we minimize the load on the database.
        $builder = $this->entityManager->createQueryBuilder();
        $builder
            ->update(DayStatistic::class, 'ds');

        switch ($reason) {
            case IncreaseReason::SPAM:
                $builder
                    ->set('ds.numberOfSpamSubmissions', 'ds.numberOfSpamSubmissions + 1');
                break;
            case IncreaseReason::VALID:
                $builder
                    ->set('ds.numberOfValidSubmissions', 'ds.numberOfValidSubmissions + 1');
                break;
            case IncreaseReason::DELAYED:
                $builder
                    ->set('ds.numberOfDelayedRequests', 'ds.numberOfDelayedRequests + 1');
                break;
            case IncreaseReason::BLOCKED:
                $builder
                    ->set('ds.numberOfBlockedRequests', 'ds.numberOfBlockedRequests + 1');
                break;
        }

        $builder
            ->set('ds.updatedAt', ':updatedAt')
            ->where('ds.date = :date')
            ->andWhere('ds.project = :project')
            ->setParameter('updatedAt', (new DateTime())->format('Y-m-d H:i:s'))
            ->setParameter('date', (new DateTime())->format('Y-m-d'))
            ->setParameter('project', $project);

        $query = $builder->getQuery();
        $res = $query->execute();
        $result = ($res > 0);

        // If no DayStatistic object for the active project and date exists, create one
        if (!$result && $create) {
            $result = $this->createDayStatistic($reason, $project);

            // If we get false back, it means that the DayStatistic element for this day already exists,
            // probably because another request added it, and now we try again to increase the statistic.
            if ($result === false) {
                $result = $this->increaseDayStatistic($reason, $project, false);
            }
        }

        return $result;
    }

    /**
     * Create a DayStatistic object for the given reason and project.
     * Returns true if everything worked correctly or false, if the
     * DayStatistic object already exists for the given day and project.
     *
     * @param IncreaseReason $reason
     * @param Project $project
     * @return bool
     *
     * @throws \Exception
     */
    protected function createDayStatistic(IncreaseReason $reason, Project $project): bool
    {
        $dayStatistic = new DayStatistic();
        $dayStatistic->setProject($project);

        switch ($reason) {
            case IncreaseReason::SPAM:
                $dayStatistic->setNumberOfSpamSubmissions(1);
                break;
            case IncreaseReason::VALID:
                $dayStatistic->setNumberOfValidSubmissions(1);
                break;
            case IncreaseReason::DELAYED:
                $dayStatistic->setNumberOfDelayedRequests(1);
                break;
            case IncreaseReason::BLOCKED:
                $dayStatistic->setNumberOfBlockedRequests(1);
                break;
        }

        $dayStatistic->setUpdatedAt(new DateTime());

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

    public function getStatisticDataForCharts(string $range): array
    {
        $startDate = DateRangeUtil::getStartDateForRange($range);
        $noSpamSubmissionsData = $spamSubmissionsData = $delayedRequestsData = $blockedRequestsData = $this->createEmptyDateArray($startDate);

        $statisticData = $this->getStatisticData($startDate);
        foreach ($statisticData['numbersByDate'] as $date => $numbers) {
            if (!isset($spamSubmissionsData[$date])) {
                continue;
            }

            $spamSubmissionsData[$date] = $numbers['numberOfSpamSubmissions'];
            $noSpamSubmissionsData[$date] = $numbers['numberOfValidSubmissions'];
            $delayedRequestsData[$date] = $numbers['numberOfDelayedRequests'];
            $blockedRequestsData[$date] = $numbers['numberOfBlockedRequests'];
        }

        return [
            $this->convertIntoChartArray($noSpamSubmissionsData),
            $this->convertIntoChartArray($spamSubmissionsData),
            array_sum($noSpamSubmissionsData),
            array_sum($spamSubmissionsData),
            $this->convertIntoChartArray($delayedRequestsData),
            $this->convertIntoChartArray($blockedRequestsData),
            array_sum($delayedRequestsData),
            array_sum($blockedRequestsData),
            $startDate,
        ];
    }

    protected function createEmptyDateArray(DateTime $startDate): array
    {
        $dateArray = [];
        $endDate = new DateTime();

        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($startDate, $interval, $endDate);

        foreach ($period as $dt) {
            $dateArray[$dt->format('Y-m-d')] = 0;
        }

        // Add the end date
        $dateArray[$endDate->format('Y-m-d')] = 0;

        return $dateArray;
    }

    protected function convertIntoChartArray($data): array
    {
        $convertedData = [];
        foreach ($data as $date => $count) {
            $convertedData[] = [
                'x' => $date,
                'y' => $count
            ];
        }

        return $convertedData;
    }
}
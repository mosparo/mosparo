<?php

namespace Mosparo\Helper;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
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
            ->select('s.submittedAt, s.valid, s.spam')
            ->from(Submission::class, 's')
            ->where('s.spam = 1 OR s.valid IS NOT NULL');

        if ($startDate) {
            $builder
                ->andWhere('s.submittedAt > :startDate')
                ->setParameter(':startDate', $startDate->format(DateTimeInterface::ATOM));
        }

        $data = ['numberOfValidSubmissions' => 0, 'numberOfSpamSubmissions' => 0, 'numbersByDate' => []];
        foreach ($builder->getQuery()->getResult() as $submissionData) {
            $type = ($submissionData['spam'] || !$submissionData['valid']) ? 'numberOfSpamSubmissions' : 'numberOfValidSubmissions';
            $data[$type]++;

            $day = $submissionData['submittedAt']->format('Y-m-d');
            if (!isset($data['numbersByDate'][$day])) {
                $data['numbersByDate'][$day] = ['numberOfValidSubmissions' => 0, 'numberOfSpamSubmissions' => 0];
            }

            $data['numbersByDate'][$day][$type]++;
        }

        return $data;
    }
}
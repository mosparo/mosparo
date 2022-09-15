<?php

namespace Mosparo\Controller\Api\V1\Statistic;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Mosparo\Entity\Submission;
use Mosparo\Helper\ProjectHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/statistic")
 */
class StatisticApiController extends AbstractController
{
    protected ProjectHelper $projectHelper;

    public function __construct(ProjectHelper $projectHelper)
    {
        $this->projectHelper = $projectHelper;
    }

    /**
     * @Route("/daily", name="statistic_api_daily")
     */
    public function summary(Request $request, EntityManagerInterface $entityManager): Response
    {
        // If there is no active project, we cannot do anything.
        if (!$this->projectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.']);
        }

        // Get the time range (in seconds) and calculate the start date
        $startDate = false;
        if ($request->query->has('range')) {
            $range = intval($request->query->get('range'));

            if ($range === 0) {
                return new JsonResponse(['error' => true, 'errorMessage' => 'Invalid range.']);
            }

            $startDate = new DateTime();
            try {
                $interval = new DateInterval('PT' . $range . 'S');
                $startDate = $startDate->sub($interval);
            } catch (Exception $e) {
                $startDate = false;
            }
        }

        // Get the submissions
        $queryBuilder = $entityManager->createQueryBuilder();
        $queryBuilder
            ->select('e')
            ->from(Submission::class, 'e')
            ->where('(e.spam = 1 OR e.valid IS NOT NULL)');

        if ($startDate) {
            $queryBuilder
                ->andWhere('e.submittedAt > :startDate')
                ->setParameter(':startDate', $startDate->format(DateTimeInterface::ATOM));
        }

        $submissions = $queryBuilder->getQuery()->getResult();

        // Collect the statistic data
        $data = ['valid' => 0, 'spam' => 0, 'days' => []];
        foreach ($submissions as $submission) {
            $type = ($submission->isSpam() || !$submission->isValid()) ? 'spam' : 'valid';
            $data[$type]++;

            $day = $submission->getSubmittedAt()->format('Y-m-d');
            if (!isset($data['days'][$day])) {
                $data['days'][$day] = ['valid' => 0, 'spam' => 0];
            }

            $data['days'][$day][$type]++;
        }

        return new JsonResponse(['result' => true, 'data' => $data]);
    }
}
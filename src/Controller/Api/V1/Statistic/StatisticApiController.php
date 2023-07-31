<?php

namespace Mosparo\Controller\Api\V1\Statistic;

use DateInterval;
use DateTime;
use Exception;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\StatisticHelper;
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
     * @Route("/by-date", name="statistic_api_by_date")
     */
    public function byDate(Request $request, StatisticHelper $statisticHelper): Response
    {
        // If there is no active project, we cannot do anything.
        if (!$this->projectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.']);
        }

        // Get the time range (in seconds) and calculate the start date
        $startDate = null;
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
        $data = $statisticHelper->getStatisticData($startDate);

        return new JsonResponse(['result' => true, 'data' => $data]);
    }
}
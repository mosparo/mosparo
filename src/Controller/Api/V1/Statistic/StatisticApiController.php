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

        $startDate = null;

        // Get the start date from the parameters
        if ($request->query->has('startDate')) {
            try {
                $startDate = new DateTime($request->query->get('startDate'));

                if ($startDate > (new DateTime())) {
                    return new JsonResponse(['error' => true, 'errorMessage' => 'The start date cannot be in the future.']);
                }
            } catch (\Exception $e) {
                return new JsonResponse(['error' => true, 'errorMessage' => 'The start date has an invalid format. Excepted format: YYYY-MM-DD']);
            }
        }

        // Get the time range (in seconds) and calculate the start date.
        // We only accept the range parameter if the start date parameter is not set
        if ($startDate === null && $request->query->has('range')) {
            $range = intval($request->query->get('range'));

            if ($range === 0) {
                return new JsonResponse(['error' => true, 'errorMessage' => 'Invalid range.']);
            }

            $startDate = new DateTime();
            try {
                $interval = new DateInterval('PT' . $range . 'S');
                $startDate = $startDate->sub($interval);
            } catch (Exception $e) {
                $startDate = null;
            }
        }

        // Get the submissions
        $data = $statisticHelper->getStatisticData($startDate);

        return new JsonResponse(['result' => true, 'data' => $data]);
    }
}
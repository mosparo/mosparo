<?php

namespace Mosparo\Controller\ProjectRelated;

use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Rule;
use Mosparo\Entity\Ruleset;
use Mosparo\Helper\LocaleHelper;
use Mosparo\Helper\StatisticHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class DashboardController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    /**
     * @Route("/", name="dashboard")
     */
    public function dashboard(Request $request, EntityManagerInterface $entityManager, LocaleHelper $localeHelper, StatisticHelper $statisticHelper): Response
    {
        [$noSpamSubmissionsData, $spamSubmissionsData, $numberOfNoSpamSubmissions, $numberOfSpamSubmissions] = $this->getSubmissionDataForChart($statisticHelper);

        $builder = $entityManager->createQueryBuilder();
        $builder
            ->select('COUNT(r.id) AS rules')
            ->from(Rule::class, 'r');
        $result = $builder->getQuery()->getOneOrNullResult();
        $numberOfRules = $result['rules'];

        $builder = $entityManager->createQueryBuilder();
        $builder
            ->select('COUNT(rs.id) AS rulesets')
            ->from(Ruleset::class, 'rs');
        $result = $builder->getQuery()->getOneOrNullResult();
        $numberOfRulesets = $result['rulesets'];

        // Get the date format for the chart
        [ , $dateFormat, , ] = $localeHelper->determineLocaleValues($request);
        $dateFormat = str_replace(['d', 'm', 'Y'], ['dd', 'MM', 'yyyy'], $dateFormat);

        return $this->render('project_related/dashboard/dashboard.html.twig', [
            'noSpamSubmissionsData' => $noSpamSubmissionsData,
            'spamSubmissionsData' => $spamSubmissionsData,
            'numberOfNoSpamSubmissions' => $numberOfNoSpamSubmissions,
            'numberOfSpamSubmissions' => $numberOfSpamSubmissions,
            'numberOfRules' => $numberOfRules,
            'numberOfRulesets' => $numberOfRulesets,
            'chartDateFormat' => $dateFormat,
        ]);
    }

    protected function getSubmissionDataForChart(StatisticHelper $statisticHelper): array
    {
        $noSpamSubmissionsData = $spamSubmissionsData = $this->createEmptyDateArray();

        $statisticData = $statisticHelper->getStatisticData();
        foreach ($statisticData['numbersByDate'] as $date => $numbers) {
            if (!isset($spamSubmissionsData[$date]) || !isset($noSpamSubmissionsData[$date])) {
                continue;
            }

            $spamSubmissionsData[$date] = $numbers['numberOfSpamSubmissions'];
            $noSpamSubmissionsData[$date] = $numbers['numberOfValidSubmissions'];
        }

        return [
            $this->convertIntoChartArray($noSpamSubmissionsData),
            $this->convertIntoChartArray($spamSubmissionsData),
            array_sum($noSpamSubmissionsData),
            array_sum($spamSubmissionsData)
        ];
    }

    protected function createEmptyDateArray(): array
    {
        $dateArray = [];
        $endDate = new DateTime();
        $startDate = (clone $endDate)->sub(DateInterval::createFromDateString('13 days'));

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
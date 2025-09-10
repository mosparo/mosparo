<?php

namespace Mosparo\Controller\ProjectRelated;

use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Rule;
use Mosparo\Entity\RulePackage;
use Mosparo\Helper\CleanupHelper;
use Mosparo\Helper\LocaleHelper;
use Mosparo\Helper\StatisticHelper;
use Mosparo\Util\DateRangeUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/project/{_projectId}')]
class DashboardController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    #[Route('/', name: 'project_dashboard')]
    #[Route('/range/{range}', name: 'project_dashboard_with_range')]
    public function dashboard(
        Request $request,
        EntityManagerInterface $entityManager,
        LocaleHelper $localeHelper,
        StatisticHelper $statisticHelper,
        CleanupHelper $cleanupHelper,
        string $range = ''
    ): Response {
        $statisticStorageLimit = $this->projectHelper->getActiveProject()->getStatisticStorageLimit();
        if (!DateRangeUtil::isValidRange($range, false, $statisticStorageLimit)) {
            $range = DateRangeUtil::DATE_RANGE_14D;
        }

        [$noSpamSubmissionsData, $spamSubmissionsData, $numberOfNoSpamSubmissions, $numberOfSpamSubmissions, $startDate] = $this->getSubmissionDataForChart($statisticHelper, $range);

        $builder = $entityManager->createQueryBuilder();
        $builder
            ->select('COUNT(r.id) AS rules')
            ->from(Rule::class, 'r');
        $result = $builder->getQuery()->getOneOrNullResult();
        $numberOfRules = $result['rules'];

        $builder = $entityManager->createQueryBuilder();
        $builder
            ->select('COUNT(rp.id) AS rule_packages')
            ->from(RulePackage::class, 'rp');
        $result = $builder->getQuery()->getOneOrNullResult();
        $numberOfRulePackages = $result['rule_packages'];

        // Get the date format for the chart
        [ , $dateFormat, , ] = $localeHelper->determineLocaleValues($request);
        $dateFormat = str_replace(['d', 'm', 'Y'], ['dd', 'MM', 'yyyy'], $dateFormat);

        $endDate = (new DateTime())->setTime(0, 0)->sub(new DateInterval('P14D'));

        return $this->render('project_related/dashboard/dashboard.html.twig', [
            'noSpamSubmissionsData' => $noSpamSubmissionsData,
            'spamSubmissionsData' => $spamSubmissionsData,
            'numberOfNoSpamSubmissions' => $numberOfNoSpamSubmissions,
            'numberOfSpamSubmissions' => $numberOfSpamSubmissions,
            'numberOfRules' => $numberOfRules,
            'numberOfRulePackages' => $numberOfRulePackages,
            'chartDateFormat' => $dateFormat,
            'dateRangeOptions' => DateRangeUtil::getChoiceOptions(false, $statisticStorageLimit),
            'activeRange' => $range,
            'statisticOnlyRangeStartDate' => $startDate->getTimestamp() * 1000,
            'statisticOnlyRangeEndDate' => $endDate->getTimestamp() * 1000,
            'lastDatabaseCleanup' => $cleanupHelper->getLastDatabaseCleanup(),
        ]);
    }

    protected function getSubmissionDataForChart(StatisticHelper $statisticHelper, string $range): array
    {
        $startDate = DateRangeUtil::getStartDateForRange($range);
        $noSpamSubmissionsData = $spamSubmissionsData = $this->createEmptyDateArray($startDate);

        $statisticData = $statisticHelper->getStatisticData($startDate);
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
            array_sum($spamSubmissionsData),
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
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

    protected DateInterval $submissionRetentionPeriod;

    public function __construct(int $submissionRetentionPeriod = 14)
    {
        $this->submissionRetentionPeriod = new DateInterval(sprintf('P%dD', ($submissionRetentionPeriod >= 1 && $submissionRetentionPeriod <= 14) ? $submissionRetentionPeriod : 14)); // Days
    }

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

        [
            $noSpamSubmissionsData,
            $spamSubmissionsData,
            $numberOfNoSpamSubmissions,
            $numberOfSpamSubmissions,
            $delayedRequestsData,
            $blockedRequestsData,
            $numberOfDelayedRequests,
            $numberOfBlockedRequests,
            $startDate
        ] = $statisticHelper->getStatisticDataForCharts($range);

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

        $endDate = (new DateTime())->setTime(0, 0)->sub($this->submissionRetentionPeriod);

        return $this->render('project_related/dashboard/dashboard.html.twig', [
            'noSpamSubmissionsData' => $noSpamSubmissionsData,
            'spamSubmissionsData' => $spamSubmissionsData,
            'numberOfNoSpamSubmissions' => $numberOfNoSpamSubmissions,
            'numberOfSpamSubmissions' => $numberOfSpamSubmissions,
            'numberOfRules' => $numberOfRules,
            'numberOfRulePackages' => $numberOfRulePackages,
            'delayedRequestsData' => $delayedRequestsData,
            'blockedRequestsData' => $blockedRequestsData,
            'numberOfDelayedRequests' => $numberOfDelayedRequests,
            'numberOfBlockedRequests' => $numberOfBlockedRequests,
            'chartDateFormat' => $dateFormat,
            'dateRangeOptions' => DateRangeUtil::getChoiceOptions(false, $statisticStorageLimit),
            'activeRange' => $range,
            'statisticOnlyRangeStartDate' => $startDate->getTimestamp() * 1000,
            'statisticOnlyRangeEndDate' => $endDate->getTimestamp() * 1000,
            'lastDatabaseCleanup' => $cleanupHelper->getLastDatabaseCleanup(),
        ]);
    }
}
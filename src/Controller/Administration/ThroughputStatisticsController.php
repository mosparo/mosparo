<?php

namespace Mosparo\Controller\Administration;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Delay;
use Mosparo\Entity\Lockout;
use Mosparo\Helper\LocaleHelper;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\StatisticHelper;
use Mosparo\Util\DateRangeUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/administration/throughput-statistics')]
class ThroughputStatisticsController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    protected LocaleHelper $localeHelper;

    protected TranslatorInterface $translator;

    protected StatisticHelper $statisticHelper;

    protected ProjectHelper $projectHelper;

    public function __construct(
        EntityManagerInterface $entityManager,
        LocaleHelper $localeHelper,
        TranslatorInterface $translator,
        StatisticHelper $statisticHelper,
        ProjectHelper $projectHelper,
    ) {
        $this->entityManager = $entityManager;
        $this->localeHelper = $localeHelper;
        $this->translator = $translator;
        $this->statisticHelper = $statisticHelper;
        $this->projectHelper = $projectHelper;
    }

    #[Route('/', name: 'administration_throughput_statistics')]
    #[Route('/range/{range}', name: 'administration_throughput_statistics_with_range')]
    public function dashboard(Request $request, string $range = ''): Response
    {
        if (!DateRangeUtil::isValidRange($range, false)) {
            $range = DateRangeUtil::DATE_RANGE_14D;
        }

        if ($this->projectHelper->hasActiveProject()) {
            $this->projectHelper->disableDoctrineFilter();
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

        ] = $this->statisticHelper->getStatisticDataForCharts($range);
        if ($this->projectHelper->hasActiveProject()) {
            $this->projectHelper->enableDoctrineFilter();
        }

        $numberOfDelays = null;
        $maxValidUntilDelay = null;
        $delayStatistics = $this->entityManager->createQueryBuilder()
            ->select('COUNT(d.id) AS numberOfDelays, MAX(d.validUntil) AS maxValidUntil')
            ->from(Delay::class, 'd')
            ->where('d.validUntil > CURRENT_TIMESTAMP()')
            ->getQuery()
            ->getSingleResult()
        ;
        if ($delayStatistics) {
            $numberOfDelays = $delayStatistics['numberOfDelays'];
            $maxValidUntilDelay = new DateTime($delayStatistics['maxValidUntil']);
        }

        $numberOfLockouts = null;
        $maxValidUntilLockout = null;
        $delayStatistics = $this->entityManager->createQueryBuilder()
            ->select('COUNT(d.id) AS numberOfLockouts, MAX(d.validUntil) AS maxValidUntil')
            ->from(Lockout::class, 'd')
            ->where('d.validUntil > CURRENT_TIMESTAMP()')
            ->getQuery()
            ->getSingleResult()
        ;
        if ($delayStatistics) {
            $numberOfLockouts = $delayStatistics['numberOfLockouts'];
            $maxValidUntilLockout = new DateTime($delayStatistics['maxValidUntil']);
        }

        // Get the date format for the chart
        [ , $dateFormat, , ] = $this->localeHelper->determineLocaleValues($request);
        $dateFormat = str_replace(['d', 'm', 'Y'], ['dd', 'MM', 'yyyy'], $dateFormat);

        return $this->render('administration/throughput_statistics/dashboard.html.twig', [
            'noSpamSubmissionsData' => $noSpamSubmissionsData,
            'spamSubmissionsData' => $spamSubmissionsData,
            'numberOfNoSpamSubmissions' => $numberOfNoSpamSubmissions,
            'numberOfSpamSubmissions' => $numberOfSpamSubmissions,
            'delayedRequestsData' => $delayedRequestsData,
            'blockedRequestsData' => $blockedRequestsData,
            'numberOfDelayedRequests' => $numberOfDelayedRequests,
            'numberOfBlockedRequests' => $numberOfBlockedRequests,
            'chartDateFormat' => $dateFormat,
            'dateRangeOptions' => DateRangeUtil::getChoiceOptions(false),
            'activeRange' => $range,
            'numberOfDelays' => $numberOfDelays,
            'maxValidUntilDelay' => $maxValidUntilDelay,
            'numberOfLockouts' => $numberOfLockouts,
            'maxValidUntilLockout' => $maxValidUntilLockout,
        ]);
    }

    #[Route('/{type}/reset', name: 'administration_throughput_statistics_reset')]
    public function delete(Request $request, string $type): Response
    {
        if (!in_array($type, ['delay', 'lockout'])) {
            return $this->redirectToRoute('administration_throughput_statistics');
        }

        if ($request->request->has('reset-token')) {
            $submittedToken = $request->request->get('reset-token');

            if ($this->isCsrfTokenValid('reset-' . $type, $submittedToken)) {
                $qb = $this->entityManager->createQueryBuilder();

                if ($type === 'delay') {
                    $qb->delete(Delay::class, 'd');
                } else if ($type === 'lockout') {
                    $qb->delete(Lockout::class, 'l');
                }

                $qb->getQuery()->execute();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'administration.throughputStatistics.reset.' . $type . '.successfullyResetted',
                        [],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('administration_throughput_statistics');
            }
        }

        return $this->render('administration/throughput_statistics/reset.html.twig', [
            'type' => $type,
        ]);
    }
}

<?php

namespace Mosparo\Controller\ProjectRelated;

use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use Mosparo\Entity\Rule;
use Mosparo\Entity\Ruleset;
use Mosparo\Entity\Submission;
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
    public function dashboard(Request $request, EntityManagerInterface $entityManager): Response
    {
        [$noSpamSubmissionsData, $spamSubmissionsData] = $this->getSubmissionDataForChart($entityManager);

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
        // @TODO: Replace the date format with an user setting
        $intlDateFormatter = new IntlDateFormatter(
            $request->getLocale(),
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE,
            'UTC',
            IntlDateFormatter::GREGORIAN
        );

        return $this->render('project_related/dashboard/dashboard.html.twig', [
            'noSpamSubmissionsData' => $noSpamSubmissionsData,
            'spamSubmissionsData' => $spamSubmissionsData,
            'numberOfRules' => $numberOfRules,
            'numberOfRulesets' => $numberOfRulesets,
            'chartDateFormat' => $intlDateFormatter->getPattern(),
        ]);
    }

    protected function getSubmissionDataForChart($entityManager): array
    {
        $noSpamSubmissionsData = $spamSubmissionsData = $this->createEmptyDateArray();

        $builder = $entityManager->createQueryBuilder();
        $builder
            ->select('s')
            ->from(Submission::class, 's')
            ->where('s.spam = 1')
            ->orWhere('s.valid IS NOT NULL');

        foreach ($builder->getQuery()->getResult() as $submission) {
            $dateKey = $submission->getSubmittedAt()->format('Y-m-d');
            if ($submission->isSpam() || !$submission->isValid()) {
                if (!isset($spamSubmissionsData[$dateKey])) {
                    continue;
                }

                $spamSubmissionsData[$dateKey]++;
            } else if ($submission->isValid()) {
                if (!isset($noSpamSubmissionsData[$dateKey])) {
                    continue;
                }

                $noSpamSubmissionsData[$dateKey]++;
            }
        }

        return [$this->convertIntoChartArray($noSpamSubmissionsData), $this->convertIntoChartArray($spamSubmissionsData)];
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
<?php

namespace Mosparo\Controller\ProjectRelated;

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
    public function dashboard(Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $builder = $entityManager->createQueryBuilder();
        $builder
            ->select('COUNT(s.id) AS submissions')
            ->from(Submission::class, 's')
            ->where('s.spam = 1')
            ->orWhere('s.valid IS NOT NULL');
        $result = $builder->getQuery()->getOneOrNullResult();
        $numberOfSubmissions = $result['submissions'] ?? 0;

        $builder = $entityManager->createQueryBuilder();
        $builder
            ->select('COUNT(s.id) AS submissions')
            ->from(Submission::class, 's')
            ->where('s.spam = 1');
        $result = $builder->getQuery()->getOneOrNullResult();
        $numberOfSpamSubmissions = $result['submissions'] ?? 0;

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

        return $this->render('project_related/dashboard/dashboard.html.twig', [
            'numberOfSubmissions' => $numberOfSubmissions,
            'numberOfSpamSubmissions' => $numberOfSpamSubmissions,
            'numberOfRules' => $numberOfRules,
            'numberOfRulesets' => $numberOfRulesets
        ]);
    }
}
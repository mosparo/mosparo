<?php

namespace Mosparo\Controller\ProjectRelated;

use Mosparo\Entity\Rule;
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
        $rulesRepository = $this->getDoctrine()->getRepository(Rule::class);

        return $this->render('project_related/dashboard/dashboard.html.twig', [
            'numberOfRules' => count($rulesRepository->findAll())
        ]);
    }
}
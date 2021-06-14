<?php

namespace Mosparo\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="dashboard")
     */
    public function dashboard(): Response
    {
        return $this->render('dashboard/dashboard.html.twig', [
        ]);
    }
}
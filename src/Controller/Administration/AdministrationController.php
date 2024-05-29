<?php

namespace Mosparo\Controller\Administration;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administration')]
class AdministrationController extends AbstractController
{
    #[Route('/', name: 'administration_overview')]
    public function general(): Response
    {
        return $this->render('administration/overview.html.twig');
    }
}
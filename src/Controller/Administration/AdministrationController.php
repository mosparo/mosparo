<?php

namespace Mosparo\Controller\Administration;

use Kir\StringUtils\Matching\Wildcards\Pattern;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/administration")
 */
class AdministrationController extends AbstractController
{
    /**
     * @Route("/", name="administration_overview")
     */
    public function general(Request $request): Response
    {
        return $this->render('administration/overview.html.twig');
    }
}
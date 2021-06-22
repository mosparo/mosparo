<?php

namespace Mosparo\Controller\ProjectRelated;

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
 * @Route("/settings")
 */
class SettingsController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    /**
     * @Route("/general", name="settings_general")
     */
    public function general(Request $request): Response
    {
        $project = $this->getActiveProject();

        // @todo: Resolve this code duplication (see Mosparo\Controller\ProjectController::create()
        $form = $this->createFormBuilder($project)
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('sites', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'help' => 'Please enter all sites which this project will include.',
                'entry_type' => TextType::class,
                'entry_options' => [
                    'attr' => [
                        'placeholder' => 'example.com'
                    ]
                ]
            ])
            ->add('status', ChoiceType::class, [
                'attr' => ['class' => 'form-select'],
                'choices' => ['Inactive' => 0, 'Active' => 1],
                'help' => 'Activate or inactivate the spam detection. If inactive, the system will log all submissions but will not prevent any submission.'
            ])
            ->add('spamScore', NumberType::class, [
                'help' => 'Defines the number from which a submission will be rated a spam. If the rating of a submission is above this nubmer, the submission is rated as spam.'
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            // Set the flash message
            $session = $request->getSession();
            $session->getFlashBag()->add('success', 'The settings were saved successfully.');

            return $this->redirectToRoute('settings_general');
        }

        return $this->render('project_related/settings/general.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }

    /**
     * @Route("/design", name="settings_design")
     */
    public function design(Request $request): Response
    {
        return $this->render('project_related/settings/design.html.twig');
    }

    /**
     * @Route("/users", name="settings_users")
     */
    public function users(Request $request): Response
    {
        return $this->render('project_related/settings/users.html.twig');
    }
}
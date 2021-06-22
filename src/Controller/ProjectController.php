<?php

namespace Mosparo\Controller;

use Mosparo\Util\TokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Mosparo\Entity\Project;

/**
 * @Route("/project")
 */
class ProjectController extends AbstractController
{
    protected $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/", name="project_list")
     */
    public function list(): Response
    {
        return $this->render('project/list.html.twig');
    }

    /**
     * @Route("/create", name="project_create")
     */
    public function create(Request $request): Response
    {
        $project = new Project();

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
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $tokenGenerator = new TokenGenerator();
            $project->setPublicKey($tokenGenerator->generateToken());
            $project->setPrivateKey($tokenGenerator->generateToken());

            $entityManager->persist($project);
            $entityManager->flush();

            // Set the flash message
            $session = $request->getSession();
            $session->getFlashBag()->add('success', 'The new project was successfully created.');

            return $this->redirectToRoute('project_list');
        }

        return $this->render('project/create.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }

    /**
     * @Route("/delete/{project}", name="project_delete")
     */
    public function delete(Request $request, Project $project): Response
    {
        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-project', $submittedToken)) {
                $entityManager = $this->getDoctrine()->getManager();

                $entityManager->remove($project);
                $entityManager->flush();

                // Set the flash message
                $session = $request->getSession();
                $session->getFlashBag()->add('error', 'The project ' . $project->getName() . ' was deleted successfully.');

                return $this->redirectToRoute('project_list');
            }
        }

        return $this->render('project/delete.html.twig', [
            'project' => $project,
        ]);
    }

    /**
     * @Route("/switch/{project}", name="project_switch")
     */
    public function switch(Request $request, Project $project): Response
    {
        // @todo: check if the user is allowed to access the project

        $this->session->set('activeProjectId', $project->getId());

        return $this->redirectToRoute('dashboard');
    }
}
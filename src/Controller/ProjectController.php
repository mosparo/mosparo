<?php

namespace Mosparo\Controller;

use Mosparo\Entity\ProjectMember;
use Mosparo\Form\ProjectFormType;
use Mosparo\Helper\CleanupHelper;
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
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/project")
 */
class ProjectController extends AbstractController
{
    protected $session;

    protected $cleanupHelper;

    protected $translator;

    public function __construct(SessionInterface $session, CleanupHelper $cleanupHelper, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->cleanupHelper = $cleanupHelper;
        $this->translator = $translator;
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

        $form = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $tokenGenerator = new TokenGenerator();
            $project->setPublicKey($tokenGenerator->generateToken());
            $project->setPrivateKey($tokenGenerator->generateToken());

            $projectMember = new ProjectMember();
            $projectMember->setProject($project);
            $projectMember->setUser($this->getUser());
            $projectMember->setRole(ProjectMember::ROLE_OWNER);

            $entityManager->persist($project);
            $entityManager->persist($projectMember);
            $entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'The new project was successfully created.',
                    [],
                    'mosparo'
                )
            );

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

                // Delete all to the project associated objects
                $this->cleanupHelper->cleanupProjectEntities($project);

                $entityManager->remove($project);
                $entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans(
                        'The project %projectName% was deleted successfully.',
                        ['%projectName%' => $project->getName()],
                        'mosparo'
                    )
                );

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
        // Only admin users or user which are added as project member have access to the project
        if (!$this->isGranted('ROLE_ADMIN') && !$project->isProjectMember($this->getUser())) {
            return $this->redirectToRoute('project_list');
        }

        $this->session->set('activeProjectId', $project->getId());

        return $this->redirectToRoute('dashboard');
    }
}
<?php

namespace Mosparo\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\Submission;
use Mosparo\Form\ProjectFormType;
use Mosparo\Helper\CleanupHelper;
use Mosparo\Helper\DesignHelper;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Util\TokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Mosparo\Entity\Project;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/project")
 */
class ProjectController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    protected CleanupHelper $cleanupHelper;

    protected TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $entityManager, CleanupHelper $cleanupHelper, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->cleanupHelper = $cleanupHelper;
        $this->translator = $translator;
    }

    /**
     * @Route("/", name="project_list")
     */
    public function list(): Response
    {
        $numberOfSubmissions = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(s.project) AS project_id', 'COUNT(s) AS count')
            ->from(Submission::class, 's')
            ->where('s.spam = 1')
            ->orWhere('s.valid IS NOT NULL')
            ->groupBy('s.project')
            ->getQuery();

        $numberOfSubmissionsByProject = [];
        foreach ($numberOfSubmissions->getResult() as $row) {
            $numberOfSubmissionsByProject[$row['project_id']] = $row['count'];
        }

        return $this->render('project/list.html.twig', [
            'numberOfSubmissionsByProject' => $numberOfSubmissionsByProject
        ]);
    }

    /**
     * @Route("/create", name="project_create")
     */
    public function create(Request $request, EntityManagerInterface $entityManager, ProjectHelper $projectHelper, DesignHelper $designHelper): Response
    {
        $project = new Project();

        $form = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tokenGenerator = new TokenGenerator();
            $project->setPublicKey($tokenGenerator->generateToken());
            $project->setPrivateKey($tokenGenerator->generateToken());

            $projectMember = new ProjectMember();
            $projectMember->setProject($project);
            $projectMember->setUser($this->getUser());
            $projectMember->setRole(ProjectMember::ROLE_OWNER);

            // Initial save
            $entityManager->persist($project);
            $entityManager->persist($projectMember);
            $entityManager->flush();

            // Set the active project
            $projectHelper->setActiveProject($project);

            // Prepare the css cache and save again
            $designHelper->generateCssCache($project);
            $entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'project.create.message.successfullyCreated',
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
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager, DesignHelper $designHelper): Response
    {
        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-project', $submittedToken)) {
                // Delete all to the project associated objects
                $this->cleanupHelper->cleanupProjectEntities($project);

                // Remove the cached resources
                $designHelper->clearCssCache($project);

                $entityManager->remove($project);
                $entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans(
                        'project.delete.message.successfullyDeleted',
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

        $request->getSession()->set('activeProjectId', $project->getId());

        return $this->redirectToRoute('dashboard');
    }
}
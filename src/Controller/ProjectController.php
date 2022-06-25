<?php

namespace Mosparo\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\ProjectMember;
use Mosparo\Form\ProjectFormType;
use Mosparo\Helper\CleanupHelper;
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
    protected CleanupHelper $cleanupHelper;

    protected TranslatorInterface $translator;

    public function __construct(CleanupHelper $cleanupHelper, TranslatorInterface $translator)
    {
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
    public function create(Request $request, EntityManagerInterface $entityManager): Response
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

            $entityManager->persist($project);
            $entityManager->persist($projectMember);
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
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-project', $submittedToken)) {
                // Delete all to the project associated objects
                $this->cleanupHelper->cleanupProjectEntities($project);

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
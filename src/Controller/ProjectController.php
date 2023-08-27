<?php

namespace Mosparo\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\Submission;
use Mosparo\Entity\User;
use Mosparo\Form\DesignSettingsFormType;
use Mosparo\Form\ProjectFormType;
use Mosparo\Helper\CleanupHelper;
use Mosparo\Helper\DesignHelper;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Util\TokenGenerator;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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

    protected ProjectHelper $projectHelper;

    protected DesignHelper $designHelper;

    protected CleanupHelper $cleanupHelper;

    protected TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $entityManager,  ProjectHelper $projectHelper, DesignHelper $designHelper, CleanupHelper $cleanupHelper, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->designHelper = $designHelper;
        $this->cleanupHelper = $cleanupHelper;
        $this->translator = $translator;
    }

    /**
     * @Route("/", name="project_list")
     * @Route("/filter/{filter}", name="project_list_filtered")
     */
    public function list(DataTableFactory $dataTableFactory, Request $request, $filter = ''): Response
    {
        // Load the view from the user configuration
        $user = $this->getUser();
        $view = 'boxes';
        if ($user instanceof User) {
            $userView = $user->getConfigValue('projectListView');

            if ($userView !== null) {
                $view = $userView;
            }
        }

        // Determine the search query
        $searchQuery = '';
        if ($request->query->has('q') && trim($request->query->get('q'))) {
            $searchQuery = $request->query->get('q');
        }

        $filters = $this->entityManager->getFilters();
        $filterEnabled = false;
        if ($filters->isEnabled('project_related_filter')) {
            $filters->disable('project_related_filter');
            $filterEnabled = true;
        }

        // Determine to which projects the user has access. If it's an admin, it has access to all projects
        $allowedProjectIds = [];
        if (!$this->isGranted('ROLE_ADMIN')) {
            foreach ($this->getUser()->getProjectMemberships() as $membership) {
                $allowedProjectIds[] = $membership->getProject()->getId();
            }
        }

        // Table view
        $table = null;
        if ($view === 'table') {
            $table = $dataTableFactory->create(['autoWidth' => true])
                ->add('name', TextColumn::class, ['label' => 'project.list.name'])
                ->add('status', TwigColumn::class, [
                    'label' => 'project.list.status',
                    'template' => 'project/list/_status.html.twig'
                ])
                ->add('actions', TwigColumn::class, [
                    'label' => 'project.list.actions',
                    'className' => 'buttons',
                    'template' => 'project/list/_actions.html.twig'
                ])
                ->createAdapter(ORMAdapter::class, [
                    'entity' => Project::class,
                    'query' => function (QueryBuilder $builder) use ($filter, $searchQuery, $allowedProjectIds) {
                        $builder
                            ->select('e')
                            ->from(Project::class, 'e');

                        if ($filter === 'active') {
                            $builder
                                ->andWhere('e.status = 1');
                        } else if ($filter === 'inactive') {
                            $builder
                                ->andWhere('e.status = 0');
                        }

                        if ($searchQuery) {
                            $builder
                                ->andWhere('e.name LIKE :searchQuery')
                                ->setParameter('searchQuery', '%' . $searchQuery . '%');
                        }

                        // Limit the possible projects to the ones the user has access to
                        if ($allowedProjectIds) {
                            $builder
                                ->andWhere('e.id IN (:projects)')
                                ->setParameter('projects', $allowedProjectIds);
                        }
                    },
                ])
                ->handleRequest($request);

            if ($table->isCallback()) {
                return $table->getResponse();
            }
        }

        // Box view
        $numberOfSubmissionsByProject = null;
        if ($view === 'boxes') {
            $builder = $this->entityManager->createQueryBuilder()
                ->select('IDENTITY(s.project) AS project_id', 'COUNT(s) AS count')
                ->from(Submission::class, 's')
                ->where('s.spam = 1')
                ->orWhere('s.valid IS NOT NULL')
                ->groupBy('s.project');

            // Limit the possible projects to the ones the user has access to
            if ($allowedProjectIds) {
                $builder
                    ->andWhere('s.project IN (:projects)')
                    ->setParameter('projects', $allowedProjectIds);
            }

            $numberOfSubmissions = $builder->getQuery();

            $numberOfSubmissionsByProject = [];
            foreach ($numberOfSubmissions->getResult() as $row) {
                $numberOfSubmissionsByProject[$row['project_id']] = $row['count'];
            }
        }

        if ($filterEnabled) {
            $filters->enable('project_related_filter');
        }

        return $this->render('project/list.html.twig', [
            'numberOfSubmissionsByProject' => $numberOfSubmissionsByProject,
            'view' => $view,
            'datatable' => $table,
            'filter' => $filter,
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * @Route("/switch-view/{view}", name="project_list_switch_view")
     */
    public function switchView($view): Response
    {
        $user = $this->getUser();

        $possibleViews = ['table', 'boxes'];
        if ($user instanceof User && in_array($view, $possibleViews)) {
            $user->setConfigValue('projectListView', $view);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('project_list');
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
            $tokenGenerator = new TokenGenerator();
            $project->setPublicKey($tokenGenerator->generateToken());
            $project->setPrivateKey($tokenGenerator->generateToken());

            $projectMember = new ProjectMember();
            $projectMember->setProject($project);
            $projectMember->setUser($this->getUser());
            $projectMember->setRole(ProjectMember::ROLE_OWNER);

            // Initial save
            $this->entityManager->persist($project);
            $this->entityManager->persist($projectMember);
            $this->entityManager->flush();

            // Set the active project
            $this->projectHelper->setActiveProject($project);

            // Prepare the css cache and save again
            $this->designHelper->generateCssCache($project);
            $this->entityManager->flush();

            return $this->redirectToRoute('project_create_wizard_design', ['project' => $project->getId()]);
        }

        return $this->render('project/create.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }

    /**
     * @Route("/create-wizard/{project}/design", name="project_create_wizard_design")
     */
    public function createWizardDesign(Request $request, Project $project): Response
    {
        if ($this->projectHelper->getActiveProject() !== $project) {
            $result = $this->setActiveProject($request, $project);

            if (!$result) {
                return $this->redirectToRoute('project_list');
            }
        }

        $project->setConfigValue('designMode', 'simple');
        $config = $project->getConfigValues();

        $form = $this->createForm(DesignSettingsFormType::class, $config, ['mode' => 'simple']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            foreach ($data as $key => $value) {
                if ($value === null) {
                    $value = '';
                }

                $project->setConfigValue($key, $value);
            }

            // Prepare the css cache
            $this->designHelper->generateCssCache($project);

            $this->entityManager->flush();

            return $this->redirectToRoute('project_create_wizard_security', ['project' => $project->getId()]);
        }

        return $this->render('project/create-wizard/design.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
            'sizeVariables' => $this->designHelper->getBoxSizeVariables(),
            'maxRadiusForLogo' => $this->designHelper->getMaxRadiusForLogo(),
            'mode' => 'simple',
        ]);
    }

    /**
     * @Route("/create-wizard/{project}/security", name="project_create_wizard_security")
     */
    public function createWizardSecurity(Request $request, Project $project): Response
    {
        if ($this->projectHelper->getActiveProject() !== $project) {
            $result = $this->setActiveProject($request, $project);

            if (!$result) {
                return $this->redirectToRoute('project_list');
            }
        }

        $config = $project->getConfigValues();
        $form = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            // Minimum time
            ->add('minimumTimeActive', CheckboxType::class, ['label' => 'settings.security.form.minimumTimeActive', 'required' => false])

            // Honeypot
            ->add('honeypotFieldActive', CheckboxType::class, ['label' => 'settings.security.form.honeypotFieldActive', 'required' => false])

            // delay
            ->add('delayActive', CheckboxType::class, ['label' => 'settings.security.form.delayActive', 'required' => false])

            // lockout
            ->add('lockoutActive', CheckboxType::class, ['label' => 'settings.security.form.lockoutActive', 'required' => false])

            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            foreach ($data as $key => $value) {
                $project->setConfigValue($key, $value);
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('project_create_wizard_connection', ['project' => $project->getId()]);
        }

        return $this->render('project/create-wizard/security.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }

    /**
     * @Route("/create-wizard/{project}/connection", name="project_create_wizard_connection")
     */
    public function createWizardConnection(Request $request, Project $project): Response
    {
        if ($this->projectHelper->getActiveProject() !== $project) {
            $result = $this->setActiveProject($request, $project);

            if (!$result) {
                return $this->redirectToRoute('project_list');
            }
        }

        return $this->render('project/create-wizard/connection.html.twig', [
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
                // Remove the cached resources
                $this->designHelper->clearCssCache($project);

                // Delete all to the project associated objects
                $this->cleanupHelper->cleanupProjectEntities($project);

                $this->entityManager->remove($project);
                $this->entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
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
        $result = $this->setActiveProject($request, $project);

        // Only admin users or user which are added as project member have access to the project
        if (!$result) {
            return $this->redirectToRoute('project_list');
        }

        // Redirect back to the originally requested path
        $targetPath = $request->query->get('targetPath', false);
        if ($targetPath) {
            return $this->redirect($targetPath);
        }

        return $this->redirectToRoute('dashboard');
    }

    protected function setActiveProject(Request $request, Project $project): bool
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$project->isProjectMember($this->getUser())) {
            return false;
        }

        $request->getSession()->set('activeProjectId', $project->getId());

        return true;
    }
}
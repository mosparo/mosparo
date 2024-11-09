<?php

namespace Mosparo\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectGroup;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\User;
use Mosparo\Form\DesignSettingsFormType;
use Mosparo\Form\ProjectFormType;
use Mosparo\Helper\CleanupHelper;
use Mosparo\Helper\DesignHelper;
use Mosparo\Helper\ProjectGroupHelper;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Util\TokenGenerator;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/project')]
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

    #[Route('/', name: 'project_list_root')]
    #[Route('/group/{projectGroup}', name: 'project_list_group')]
    #[Route('/filter/{filter}', name: 'project_list_filtered_root')]
    #[Route('/group/{projectGroup}/filter/{filter}', name: 'project_list_filtered_group')]
    public function list(DataTableFactory $dataTableFactory, Request $request, $filter = '', ProjectGroup $projectGroup = null): Response
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

        $tree = clone $this->projectHelper->getByUserAccessibleProjectTree();
        $routeSuffix = 'root';

        // Find the subtree for the requested project group
        if ($projectGroup) {
            $tree = $tree->findChildForProjectGroup($projectGroup);
            $routeSuffix = 'group';
        }

        // Determine the search query
        $searchQuery = '';
        if ($request->query->has('q') && trim($request->query->get('q'))) {
            $searchQuery = $request->query->get('q');

            $tree->findNodesForSearchTerm($searchQuery);
        }

        $activeProject = null;
        if ($this->projectHelper->hasActiveProject()) {
            $activeProject = $this->projectHelper->getActiveProject();
            $this->projectHelper->unsetActiveProject();
        }

        // Box view
        $numberOfSubmissionsByProject = null;

        if ($activeProject) {
            $this->projectHelper->setActiveProject($activeProject);
        }

        $baseQuery = [];
        $listQuery = [];
        if ($projectGroup) {
            $listQuery['projectGroup'] = $projectGroup->getId();
        }

        if ($searchQuery) {
            $baseQuery['q'] = $searchQuery;
            $listQuery['q'] = $searchQuery;
        }

        return $this->render('project/list.html.twig', [
            'treeNode' => $tree,
            'numberOfSubmissionsByProject' => $numberOfSubmissionsByProject,
            'view' => $view,
            'projectGroup' => $projectGroup,
            'filter' => $filter,
            'searchQuery' => $searchQuery,
            'baseQuery' => $baseQuery,
            'listQuery' => http_build_query($listQuery),
            'routeSuffix' => $routeSuffix,
        ]);
    }

    #[Route('/switch-view/{view}', name: 'project_list_switch_view')]
    public function switchView($view): Response
    {
        $user = $this->getUser();

        $possibleViews = ['table', 'boxes'];
        if ($user instanceof User && in_array($view, $possibleViews)) {
            $user->setConfigValue('projectListView', $view);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('project_list_root');
    }

    #[Route('/create', name: 'project_create_root')]
    #[Route('/group/{projectGroup}/create', name: 'project_create_group')]
    public function create(Request $request, ProjectGroupHelper $projectGroupHelper, ProjectGroup $projectGroup = null): Response
    {
        $project = new Project();
        if ($projectGroup) {
            $project->setProjectGroup($projectGroup);
        }

        $tree = $projectGroupHelper->getFullProjectGroupTreeForUser();
        $tree->sort();

        $form = $this->createForm(ProjectFormType::class, $project, [
            'tree' => $tree
        ]);
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

            return $this->redirectToRoute('project_create_wizard_design', ['_projectId' => $project->getId()]);
        }

        return $this->render('project/create.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }

    #[Route('/{_projectId}/create-wizard/design', name: 'project_create_wizard_design')]
    public function createWizardDesign(Request $request): Response
    {
        $project = $this->projectHelper->getActiveProject();
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

            return $this->redirectToRoute('project_create_wizard_security', ['_projectId' => $project->getId()]);
        }

        return $this->render('project/create-wizard/design.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
            'sizeVariables' => $this->designHelper->getBoxSizeVariables(),
            'maxRadiusForLogo' => $this->designHelper->getMaxRadiusForLogo(),
            'mode' => 'simple',
        ]);
    }

    #[Route('/{_projectId}/create-wizard/security', name: 'project_create_wizard_security')]
    public function createWizardSecurity(Request $request): Response
    {
        $project = $this->projectHelper->getActiveProject();
        $config = $project->getConfigValues();
        $form = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            // Minimum time
            ->add('minimumTimeActive', CheckboxType::class, ['label' => 'settings.security.form.minimumTimeActive', 'required' => false])

            // Honeypot
            ->add('honeypotFieldActive', CheckboxType::class, ['label' => 'settings.security.form.honeypotFieldActive', 'required' => false])

            // Delay
            ->add('delayActive', CheckboxType::class, ['label' => 'settings.security.form.delayActive', 'required' => false])

            // Lockout
            ->add('lockoutActive', CheckboxType::class, ['label' => 'settings.security.form.lockoutActive', 'required' => false])

            // Proof of work
            ->add('proofOfWorkActive', CheckboxType::class, ['label' => 'settings.security.form.proofOfWorkActive', 'required' => false])

            // Equal submissions
            ->add('equalSubmissionsActive', CheckboxType::class, ['label' => 'settings.security.form.equalSubmissionsActive', 'required' => false])

            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            foreach ($data as $key => $value) {
                $project->setConfigValue($key, $value);
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('project_create_wizard_connection', ['_projectId' => $project->getId()]);
        }

        return $this->render('project/create-wizard/security.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }

    #[Route('/{_projectId}/create-wizard/connection', name: 'project_create_wizard_connection')]
    public function createWizardConnection(Request $request): Response
    {
        $project = $this->projectHelper->getActiveProject();
        return $this->render('project/create-wizard/connection.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{_projectId}/delete', name: 'project_delete')]
    public function delete(Request $request): Response
    {
        $project = $this->projectHelper->getActiveProject();

        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-project', $submittedToken)) {
                $projectGroup = $project->getProjectGroup();

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

                if ($projectGroup) {
                    return $this->redirectToRoute('project_list_group', ['projectGroup' => $projectGroup]);
                } else {
                    return $this->redirectToRoute('project_list_root');
                }
            }
        }

        return $this->render('project/delete.html.twig', [
            'project' => $project,
        ]);
    }

    protected function findVisibleProjects(?ProjectGroup $projectGroup, string $searchQuery, string $filter, array $allowedProjectIds): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('e')
            ->from(Project::class, 'e');

        if ($projectGroup) {
            $qb
                ->where('e.projectGroup = :projectGroup')
                ->setParameter('projectGroup', $projectGroup);
        } else {
            $qb
                ->where('e.projectGroup IS NULL');
        }

        if ($filter === 'active') {
            $qb
                ->andWhere('e.status = 1');
        } else if ($filter === 'inactive') {
            $qb
                ->andWhere('e.status = 0');
        }

        if ($searchQuery) {
            $qb
                ->andWhere('e.name LIKE :searchQuery')
                ->setParameter('searchQuery', '%' . $searchQuery . '%');
        }

        // Limit the possible projects to the ones the user has access to
        if ($allowedProjectIds) {
            $qb
                ->andWhere('e.id IN (:projects)')
                ->setParameter('projects', $allowedProjectIds);
        }

        $qb->orderBy('e.name', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
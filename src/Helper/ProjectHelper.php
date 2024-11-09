<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectMember;
use Mosparo\Twig\Tree\ProjectTreeNode;
use Symfony\Bundle\SecurityBundle\Security;

class ProjectHelper
{
    protected EntityManagerInterface $entityManager;

    protected Security $security;

    protected ProjectGroupHelper $projectGroupHelper;

    protected ?Project $activeProject = null;

    protected ?ProjectTreeNode $tree = null;

    public function __construct(EntityManagerInterface $entityManager, Security $security, ProjectGroupHelper $projectGroupHelper)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->projectGroupHelper = $projectGroupHelper;
    }

    public function setActiveProject(Project $activeProject)
    {
        $this->activeProject = $activeProject;

        $this->enableDoctrineFilter();
    }

    public function unsetActiveProject()
    {
        $this->activeProject = null;

        $this->disableDoctrineFilter();
    }

    public function getActiveProject(): ?Project
    {
        return $this->activeProject;
    }

    public function hasActiveProject(): bool
    {
        return ($this->activeProject !== null);
    }

    public function hasRequiredRole($requiredRoles, Project $project = null): bool
    {
        if ($project === null) {
            if ($this->activeProject === null) {
                return false;
            }

            $project = $this->activeProject;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $projectMember = $project->getProjectMember($this->security->getUser());
        if ($projectMember === null) {
            return false;
        }

        if (in_array($projectMember->getRole(), $requiredRoles)) {
            return true;
        }

        return false;
    }

    public function canManage(Project $project = null): bool
    {
        if ($project === null) {
            if ($this->activeProject === null) {
                return false;
            }

            $project = $this->activeProject;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $projectMember = $project->getProjectMember($this->security->getUser());
        if ($projectMember === null) {
            return false;
        }

        if (in_array($projectMember->getRole(), [ProjectMember::ROLE_EDITOR, ProjectMember::ROLE_OWNER])) {
            return true;
        }

        return false;
    }

    public function getByUserAccessibleProjectTree(): ProjectTreeNode
    {
        if ($this->tree) {
            return $this->tree;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $projectRepository = $this->entityManager->getRepository(Project::class);
            $rootProjects = $projectRepository->findBy(['projectGroup' => null], ['name' => 'ASC']);

            $tree = $this->projectGroupHelper->getFullProjectGroupTreeForUser();

            foreach ($rootProjects as $project) {
                $tree->addProject($project);
            }
        } else {
            $projects = $this->getByUserAccessibleProjects();
            $tree = $this->projectGroupHelper->getProjectTreeForProjects($projects);
        }

        $tree->sort();

        $this->tree = $tree;

        return $tree;
    }

    public function getByUserAccessibleProjects(): array
    {
        $projects = [];

        if ($this->security->getUser() === null) {
            return [];
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $projectRepository = $this->entityManager->getRepository(Project::class);
            $projects = $projectRepository->findBy([], ['name' => 'ASC']);
        } else {
            $user = $this->security->getUser();
            foreach ($user->getProjectMemberships() as $membership) {
                $projects[] = $membership->getProject();
            }
        }

        return $projects;
    }

    public function enableDoctrineFilter()
    {
        $this->entityManager
            ->getFilters()
            ->enable('project_related_filter')
            ->setProjectHelper($this);
    }

    public function disableDoctrineFilter()
    {
        if (!$this->entityManager->getFilters()->isEnabled('project_related_filter')) {
            return;
        }

        $this->entityManager
            ->getFilters()
            ->disable('project_related_filter');
    }
}
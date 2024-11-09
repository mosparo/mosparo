<?php

namespace Mosparo\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\ProjectMember;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Twig\Tree\ProjectTreeNode;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Mosparo\Entity\Project;
use Twig\TwigFunction;

class ProjectExtension extends AbstractExtension implements GlobalsInterface
{
    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected Security $security;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('can_user_manage_project', [$this, 'canUserManageProject']),
            new TwigFunction('get_by_user_accessible_projects', [$this, 'getByUserAccessibleProjects']),
            new TwigFunction('get_by_user_accessible_project_tree', [$this, 'getByUserAccessibleProjectTree']),
        ];
    }

    public function canUserManageProject(Project $project): ?bool
    {
        if ($this->security->getUser() === null) {
            return false;
        }

        return $this->projectHelper->hasRequiredRole([ProjectMember::ROLE_OWNER], $project);
    }

    public function getByUserAccessibleProjectTree(): ProjectTreeNode
    {
        return $this->projectHelper->getByUserAccessibleProjectTree();
    }

    public function getByUserAccessibleProjects(): array
    {
        return $this->projectHelper->getByUserAccessibleProjects();
    }

    public function getGlobals(): array
    {
        if ($this->security->getUser() === null) {
            return [];
        }

        $canManage = false;
        $isOwner = false;
        $activeProject = $this->projectHelper->getActiveProject();
        if ($activeProject !== null) {
            if ($this->security->isGranted('ROLE_ADMIN') || $activeProject->isProjectOwner($this->security->getUser())) {
                $isOwner = true;
            }

            if ($this->projectHelper->canManage($activeProject)) {
                $canManage = true;
            }
        }

        return [
            'activeProject' => $activeProject,
            'isOwner' => $isOwner,
            'canManage' => $canManage,
        ];
    }

    protected function sortProjects(Project $a, Project $b): int
    {
        if ($a->getName() > $b->getName()) {
            return 1;
        } else if ($a->getName() < $b->getName()) {
            return -1;
        } else {
            return 0;
        }
    }
}
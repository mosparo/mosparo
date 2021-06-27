<?php

namespace Mosparo\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\ProjectMember;
use Mosparo\Helper\ProjectHelper;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Mosparo\Entity\Project;
use Twig\TwigFunction;

class ProjectExtension extends AbstractExtension implements GlobalsInterface
{
    protected $entityManager;

    protected $projectHelper;

    protected $security;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('can_user_manage_project', [$this, 'canUserManageProject'])
        ];
    }

    public function canUserManageProject(Project $project): ?bool
    {
        if ($this->security->getUser() === null) {
            return false;
        }

        return $this->projectHelper->hasRequiredRole([ProjectMember::ROLE_OWNER], $project);
    }

    public function getGlobals(): array
    {
        if ($this->security->getUser() === null) {
            return [];
        }

        $projectRepository = $this->entityManager->getRepository(Project::class);
        $projects = [];

        // Admins have access to all projects
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $projects = $projectRepository->findBy([], ['name' => 'ASC']);
        } else {
            $user = $this->security->getUser();
            foreach ($user->getProjectMemberships() as $membership) {
                $projects[] = $membership->getProject();
            }
        }

        usort($projects, [$this, 'sortProjects']);

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
            'projects' => $projects
        ];
    }

    protected function sortProjects(Project $a, Project $b)
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
<?php

namespace Mosparo\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Helper\ActiveProjectHelper;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Mosparo\Entity\Project;

class ProjectExtension extends AbstractExtension implements GlobalsInterface
{
    protected $entityManager;

    protected $activeProjectHelper;

    public function __construct(EntityManagerInterface $entityManager, ActiveProjectHelper $activeProjectHelper)
    {
        $this->entityManager = $entityManager;
        $this->activeProjectHelper = $activeProjectHelper;
    }

    public function getGlobals(): array
    {
        $projectRepository = $this->entityManager->getRepository(Project::class);
        $projects = $projectRepository->findBy([], ['name' => 'ASC']);

        return [
            'activeProject' => $this->activeProjectHelper->getActiveProject(),
            'projects' => $projects
        ];
    }
}
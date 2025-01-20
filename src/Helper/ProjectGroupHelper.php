<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\ProjectGroup;
use Mosparo\Twig\Tree\ProjectTreeNode;

class ProjectGroupHelper
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getProjectGroupTree(): array
    {
        $projectGroupRepository = $this->entityManager->getRepository(ProjectGroup::class);
        $rootGroups = $projectGroupRepository->findBy(['parent' => null], ['name' => 'ASC']);

        return $rootGroups;
    }

    public function getFullProjectGroupTreeForUser(): ProjectTreeNode
    {
        $root = new ProjectTreeNode(null);
        $tree = $this->getProjectGroupTree();

        foreach ($tree as $projectGroup) {
            $this->convertProjectGroupToTreeNode($projectGroup, $root);
        }

        return $root;
    }

    public function getProjectTreeForProjects(array $projects): ProjectTreeNode
    {
        $root = new ProjectTreeNode(null);

        foreach ($projects as $project) {
            if ($project->getProjectGroup() !== null) {
                $parent = $root->findChildForProjectGroup($project->getProjectGroup());

                if (!$parent) {
                    $parent = $this->createTreeNodeForProjectGroup($project->getProjectGroup(), $root);
                }

                $parent->addProject($project);
            } else {
                $root->addProject($project);
            }
        }

        return $root;
    }

    protected function convertProjectGroupToTreeNode(ProjectGroup $projectGroup, ProjectTreeNode $parent)
    {
        $node = new ProjectTreeNode($projectGroup);
        $parent->addChild($node);

        foreach ($projectGroup->getChildren() as $child) {
            $this->convertProjectGroupToTreeNode($child, $node);
        }

        foreach ($projectGroup->getProjects() as $project) {
            $node->addProject($project);
        }
    }

    protected function createTreeNodeForProjectGroup(?ProjectGroup $projectGroup, ProjectTreeNode $root): ProjectTreeNode
    {
        $node = new ProjectTreeNode($projectGroup);

        if ($projectGroup->getParent() === null) {
            $parent = $root;
        } else {
            $parent = $root->findChildForProjectGroup($projectGroup->getParent());
            if (!$parent) {
                $parent = $this->createTreeNodeForProjectGroup($projectGroup->getParent(), $root);
            }
        }

        $parent->addChild($node);

        return $node;
    }
}
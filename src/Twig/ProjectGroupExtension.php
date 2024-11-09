<?php

namespace Mosparo\Twig;

use Mosparo\Entity\ProjectGroup;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ProjectGroupExtension extends AbstractExtension
{

    public function getFunctions(): array
    {
        return [
            new TwigFunction('find_project_group_form_field', [$this, 'findProjectGroupFormField'])
        ];
    }

    public function findProjectGroupFormField(FormView $form, ?ProjectGroup $group): ?FormView
    {
        foreach ($form->getIterator() as $child) {
            if (!isset($child->vars['attr']['data-project-group-id'])) {
                if (!$group) {
                    return $child;
                }
                continue;
            }

            if ($child->vars['attr']['data-project-group-id'] === $group->getId()) {
                return $child;
            }
        }

        return null;
    }
}
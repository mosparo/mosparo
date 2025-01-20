<?php

namespace Mosparo\Form;

use Mosparo\Entity\ProjectGroup;
use Mosparo\Twig\Tree\ProjectTreeNode;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectGroupSelectorType extends ChoiceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['placeholder'] = 'projectGroup.mainGroup';

        $options['choices'] = $this->buildChoices($options['tree']);

        $options['choice_label'] = function (ProjectGroup $choice) {
            return $choice->getName();
        };
        $options['choice_attr'] = function (ProjectGroup $choice, string $key, mixed $value) use ($options) {
            $attr = ['data-project-group-id' => $choice->getId()];
            if ($this->isBlockedGroup($choice, $options['active_group'])) {
                $attr['disabled'] = 'disabled';
            }

            if ($choice === $options['active_group']) {
                $attr['class'] = 'active-group';
            }

            return $attr;
        };

        parent::buildForm($builder, $options);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['tree'] = $options['tree'];
        $view->vars['active_group'] = $options['active_group'];

        parent::buildView($view, $form, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'tree' => null,
            'active_group' => null,
        ]);

        parent::configureOptions($resolver);
    }

    public function getBlockPrefix(): string
    {
        return 'project_group_selector';
    }

    protected function buildChoices(ProjectTreeNode $node): array
    {
        $choices = [];

        if ($node === null) {
            return [];
        }

        foreach ($node->getChildren() as $child) {
            $choices[] = $child->getProjectGroup();

            if ($child->hasChildren()) {
                $choices = array_merge($choices, $this->buildChoices($child));
            }
        }

        return $choices;
    }

    protected function isBlockedGroup(ProjectGroup $projectGroup, ?ProjectGroup $blockedGroup): bool
    {
        if (!$blockedGroup) {
            return false;
        }

        if ($projectGroup === $blockedGroup) {
            return true;
        }

        if ($projectGroup->getParent()) {
            return $this->isBlockedGroup($projectGroup->getParent(), $blockedGroup);
        }

        return false;
    }
}

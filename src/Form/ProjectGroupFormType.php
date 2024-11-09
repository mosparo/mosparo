<?php

namespace Mosparo\Form;

use Mosparo\Entity\ProjectGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectGroupFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'projectGroup.form.name'])
            ->add('description', TextareaType::class, [
                'label' => 'projectGroup.form.description',
                'required' => false,
            ])
            ->add('parent', ProjectGroupSelectorType::class, [
                'label' => 'projectGroup.form.parentGroup',
                'required' => false,
                'expanded' => true,
                'tree' => $options['tree'] ?? null,
                'active_group' => $options['active_group'] ?? null,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectGroup::class,
            'translation_domain' => 'mosparo',
            'tree' => null,
            'active_group' => null,
        ]);
    }
}

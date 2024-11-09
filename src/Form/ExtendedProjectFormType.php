<?php

namespace Mosparo\Form;

use Mosparo\Entity\Project;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExtendedProjectFormType extends ProjectFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'project.form.status',
                'attr' => ['class' => 'form-select'],
                'choices' => ['state.inactive' => 0, 'state.active' => 1],
                'help' => 'project.form.statusHelp'
            ])
            ->add('spamScore', NumberType::class, [
                'label' => 'project.form.spamScore',
                'help' => 'project.form.spamScoreHelp',
                'html5' => true,
                'scale' => 1,
                'attr' => [
                    'min' => 0.1,
                    'step' => 'any',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'translation_domain' => 'mosparo',
            'tree' => null,
        ]);
    }
}

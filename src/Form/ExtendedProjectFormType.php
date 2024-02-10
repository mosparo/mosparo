<?php

namespace Mosparo\Form;

use Mosparo\Entity\Project;
use Mosparo\Util\DateRangeUtil;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
            ->add('statisticStorageLimit', ChoiceType::class, [
                'label' => 'project.form.statisticStorageLimit',
                'attr' => ['class' => 'form-select'],
                'choices' => DateRangeUtil::getChoiceOptions(),
                'help' => 'project.form.statisticStorageLimitHelp'
            ])
            ->add('apiDebugMode', CheckboxType::class, [
                'label' => 'project.form.apiDebugMode',
                'help' => 'project.form.apiDebugModeHelp',
                'required' => false,
            ])
            ->add('verificationSimulationMode', CheckboxType::class, [
                'label' => 'project.form.verificationSimulationMode',
                'help' => 'project.form.verificationSimulationModeHelp',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'translation_domain' => 'mosparo',
        ]);
    }
}

<?php

namespace Mosparo\Form;

use Mosparo\Entity\Project;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExtendedProjectFormType extends ProjectFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'attr' => ['class' => 'form-select'],
                'choices' => ['Inactive' => 0, 'Active' => 1],
                'help' => 'Activate or inactivate the spam detection. If inactive, the system will log all submissions but will not prevent any submission.'
            ])
            ->add('spamScore', NumberType::class, [
                'label' => 'Spam score',
                'help' => 'Defines the number from which a submission will be rated a spam. If the rating of a submission is above this nubmer, the submission is rated as spam.'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}

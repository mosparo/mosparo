<?php

namespace Mosparo\Form\FieldType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ValueWithUnitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value', NumberType::class, array_merge([
                'label' => 'vwuft.value',
                'html5' => true,
                'scale' => 1,
                'attr' => ['class' => 'text-end', 'min' => 0.0, 'autocomplete' => 'off'],
            ], $options['valueOptions']))
            ->add('unit', ChoiceType::class, array_merge([
                'label' => 'vwuft.unit',
                'attr' => ['class' => 'form-select'],
                'choices' => $options['units'] ?? [],
                'help' => 'project.form.statusHelp',
                'expanded' => false,
                'multiple' => false,
            ], $options['unitOptions']))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'mosparo',
            'units' => [],
            'valueOptions' => [],
            'unitOptions' => [],
        ]);
    }
}

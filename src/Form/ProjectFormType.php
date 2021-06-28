<?php

namespace Mosparo\Form;

use Mosparo\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name'])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('hosts', CollectionType::class, [
                'label' => 'Hosts',
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'help' => 'Please enter all hosts which this project will include.',
                'entry_type' => TextType::class,
                'entry_options' => [
                    'attr' => [
                        'placeholder' => 'example.com'
                    ]
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'translation_domain' => 'mosparo',
        ]);
    }
}

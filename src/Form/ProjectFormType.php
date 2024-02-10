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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'project.form.name'])
            ->add('description', TextareaType::class, [
                'label' => 'project.form.description',
                'required' => false,
            ])
            ->add('hosts', CollectionType::class, [
                'label' => 'project.form.hosts',
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'help' => 'project.form.hostsHelp',
                'entry_type' => TextType::class,
                'entry_options' => [
                    'attr' => [
                        'placeholder' => 'example.com'
                    ]
                ]
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

<?php

namespace Mosparo\Form;

use Doctrine\DBAL\Types\FloatType;
use Mosparo\Entity\Rule;
use Mosparo\Entity\Ruleset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RulesetFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name'])
            ->add('url', UrlType::class, ['label' => 'URL'])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => ['Inactive' => 0, 'Active' => 1],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('spamRatingFactor', NumberType::class, [
                'label' => 'Spam rating factor',
                'required' => false,

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ruleset::class,
        ]);
    }
}

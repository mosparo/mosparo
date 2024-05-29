<?php

namespace Mosparo\Form;

use Mosparo\Entity\Ruleset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RulesetFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'ruleset.form.name'])
            ->add('url', UrlType::class, ['label' => 'ruleset.form.url'])
            ->add('status', ChoiceType::class, [
                'label' => 'ruleset.form.status',
                'choices' => ['state.inactive' => 0, 'state.active' => 1],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('spamRatingFactor', NumberType::class, [
                'label' => 'ruleset.form.spamRatingFactor',
                'required' => false,
                'help' => 'ruleset.form.spamRatingFactorHelp',
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
            'data_class' => Ruleset::class,
            'translation_domain' => 'mosparo',
        ]);
    }
}

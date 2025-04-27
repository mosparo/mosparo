<?php

namespace Mosparo\Form;

use Mosparo\Entity\RulePackage;
use Mosparo\Enum\RulePackageType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RulePackageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'rulePackage.form.name'])
            ->add('status', ChoiceType::class, [
                'label' => 'rulePackage.form.status',
                'choices' => ['state.inactive' => 0, 'state.active' => 1],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('spamRatingFactor', NumberType::class, [
                'label' => 'rulePackage.form.spamRatingFactor',
                'required' => false,
                'help' => 'rulePackage.form.spamRatingFactorHelp',
                'html5' => true,
                'scale' => 1,
                'attr' => [
                    'min' => 0.1,
                    'step' => 'any',
                ]
            ])
        ;

        $rulePackage = $builder->getData();
        if ($rulePackage->getType() === RulePackageType::AUTOMATICALLY_FROM_URL) {
            $builder
                ->add('source', UrlType::class, ['label' => 'rulePackage.form.sourceUrl', 'help' => 'rulePackage.form.sourceUrlHelp']);
        } else if ($rulePackage->getType() === RulePackageType::AUTOMATICALLY_FROM_FILE) {
            $builder
                ->add('source', TextType::class, ['label' => 'rulePackage.form.sourceFile', 'help' => 'rulePackage.form.sourceFileHelp']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RulePackage::class,
            'translation_domain' => 'mosparo',
        ]);
    }
}

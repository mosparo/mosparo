<?php

namespace Mosparo\Form;

use Mosparo\Util\ChoicesUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RuleAddMultipleItemsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $ruleType = $options['rule_type'];
        if ($ruleType === null) {
            return;
        }

        $choices = ChoicesUtil::buildChoices($ruleType->getSubtypes());
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'rule.form.addMultipleItems.label',
                'choices' => $choices,
                'attr' => [
                    'readonly' => (count($choices) === 1),
                    'class' => 'form-select rule-item-type'
                ]
            ])
            ->add('items', TextareaType::class, ['label' => 'rule.form.addMultipleItems.items', 'help' => 'Add one item per line.'])
            ->add('rating', NumberType::class, [
                'label' => 'rule.form.addMultipleItems.rating',
                'required' => false,
                'help' => 'rule.form.addMultipleItems.ratingHelp'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'rule_type' => null,
            'translation_domain' => 'mosparo',
        ]);
    }
}
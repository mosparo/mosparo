<?php

namespace Mosparo\Rule\Form;

use Mosparo\Util\ChoicesUtil;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailFormType extends AbstractRuleTypeFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $ruleType = $options['rule_type'];
        if ($ruleType === null) {
            return;
        }

        $choices = ChoicesUtil::buildChoices($ruleType->getSubtypes());
        $builder
            ->add('type', ChoiceType::class, ['choices' => $choices, 'attr' => ['readonly' => (count($choices) === 1), 'class' => 'form-select rule-item-type']])
            ->add('value', EmailType::class, ['attr' => ['placeholder' => 'rule.type.email.placeholder', 'class' => 'rule-item-value']])
            ->add('spamRatingFactor', NumberType::class, [
                'required' => false,
                'html5' => true,
                'scale' => 1,
                'attr' => [
                    'placeholder' => 'rule.type.rating.placeholder',
                    'class' => 'rule-item-rating',
                    'min' => 0.1,
                    'step' => 'any',
                ]
            ])
        ;
    }
}

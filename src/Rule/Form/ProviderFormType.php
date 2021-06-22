<?php

namespace Mosparo\Rule\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ProviderFormType extends AbstractRuleTypeFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $ruleType = $options['rule_type'];
        if ($ruleType === null) {
            return;
        }

        $choices = $this->buildChoices($ruleType->getSubtypes());
        $builder
            ->add('type', ChoiceType::class, ['choices' => $choices, 'attr' => ['readonly' => (count($choices) === 1), 'class' => 'form-select rule-item-type']])
            ->add('value', TextType::class, ['attr' => ['placeholder' => 'IP address/Subnet/ASN', 'class' => 'rule-item-value']])
            ->add('rating', NumberType::class, ['required' => false, 'attr' => ['placeholder' => 'Rating', 'class' => 'rule-item-rating']])
        ;
    }
}

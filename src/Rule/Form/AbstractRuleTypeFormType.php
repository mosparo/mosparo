<?php

namespace Mosparo\Rule\Form;

use Mosparo\Entity\RuleItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractRuleTypeFormType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'rule_type' => null,
            'data_class' => RuleItem::class
        ]);
    }
}

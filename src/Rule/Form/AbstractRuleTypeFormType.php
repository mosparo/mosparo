<?php

namespace Mosparo\Rule\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractRuleTypeFormType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'rule_type' => null
        ]);
    }

    protected function buildChoices(array $subtypes): array
    {
        $choices = [];
        foreach ($subtypes as $subtype) {
            $choices[$subtype['name']] = $subtype['key'];
        }

        return $choices;
    }
}

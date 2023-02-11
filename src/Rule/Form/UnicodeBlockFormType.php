<?php

namespace Mosparo\Rule\Form;

use Mosparo\Util\ChoicesUtil;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use zepi\Unicode\UnicodeIndex;

class UnicodeBlockFormType extends AbstractRuleTypeFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $ruleType = $options['rule_type'];
        if ($ruleType === null) {
            return;
        }

        $locale = $options['locale'] ?? '';

        $unicodeIndex = new UnicodeIndex();
        $blockChoices = [];
        foreach ($unicodeIndex->getIndex() as $key => $className) {
            $block = new $className();
            $blockChoices[$block->getName($locale)] = $key;
        }

        uksort($blockChoices, [$this, 'sortBlocks']);

        $choices = ChoicesUtil::buildChoices($ruleType->getSubtypes());
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'rule.form.items.type',
                'choices' => $choices,
                'attr' => ['readonly' => (count($choices) === 1), 'class' => 'form-select rule-item-type']
            ])
            ->add('value', ChoiceType::class, [
                'label' => 'rule.type.unicodeBlock.label',
                'choices' => $blockChoices,
                'attr' => ['class' => 'form-select rule-item-value']
            ])
            ->add('spamRatingFactor', NumberType::class, [
                'label' => 'rule.form.items.rating',
                'required' => false,
                'html5' => true,
                'scale' => 1,
                'attr' => [
                    'placeholder' => '1.0',
                    'class' => 'rule-item-rating',
                    'min' => 0.1,
                    'step' => 'any',
                ]
            ])
        ;
    }

    public function sortBlocks($keyA, $keyB)
    {
        $keyA = mb_strtolower($keyA);
        $keyB = mb_strtolower($keyB);

        $pattern = ['ä', 'ö', 'ü'];
        $replacement = ['a', 'o', 'u'];

        $keyA = str_replace($pattern, $replacement, $keyA);
        $keyB = str_replace($pattern, $replacement, $keyB);

        if ($keyA < $keyB) {
            return -1;
        } else if ($keyA > $keyB) {
            return 1;
        } else {
            return 0;
        }
    }
}

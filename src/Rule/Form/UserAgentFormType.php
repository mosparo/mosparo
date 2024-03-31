<?php

namespace Mosparo\Rule\Form;

use Mosparo\Util\ChoicesUtil;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserAgentFormType extends AbstractRuleTypeFormType
{
    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $ruleType = $options['rule_type'];
        if ($ruleType === null) {
            return;
        }

        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'rule.form.items.type',
                'choices' => ChoicesUtil::buildChoices($ruleType->getSubtypes()),
                'attr' => ['class' => 'form-select rule-item-type']
            ])
            ->add('value', TextType::class, [
                'label' => 'rule.type.userAgent.label',
                'attr' => ['class' => 'rule-item-value']
            ])
            ->add('spamRatingFactor', NumberType::class, [
                'label' => 'rule.form.items.rating',
                'required' => false,
                'html5' => true,
                'scale' => 1,
                'attr' => [
                    'placeholder' => '1.0',
                    'class' => 'rule-item-rating',
                    'min' => -1000000,
                    'max' => 1000000,
                    'step' => 'any',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'constraints' => [
                new Callback(function ($object, ExecutionContextInterface $context) {
                    if ($object->getType() === 'regex') {
                        if (@preg_match($object->getValue(), null) === false) {
                            $context->buildViolation($this->translator->trans('rule.type.userAgent.regex.validation.invalidRegexPattern', ['%error%' => preg_last_error_msg()], 'mosparo'))
                                    ->atPath('value')
                                    ->addViolation();
                        }
                    }
                }),
            ],
        ]);
    }
}

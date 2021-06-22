<?php

namespace Mosparo\Form;

use Doctrine\DBAL\Types\FloatType;
use Mosparo\Entity\Rule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $ruleType = $options['rule_type'];
        if ($ruleType === null) {
            return;
        }

        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, ['required' => false])
            ->add('status', ChoiceType::class, [
                'choices' => ['Inactive' => 0, 'Active' => 1],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('spamRatingFactor', NumberType::class, ['required' => false])
            ->add('items', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'entry_type' => $ruleType->getFormClass(),
                'entry_options' => [
                    'rule_type' => $ruleType
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Rule::class,
            'rule_type' => null
        ]);
    }
}

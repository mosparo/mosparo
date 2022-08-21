<?php

namespace Mosparo\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $firstFieldLabel = 'password.form.password';
        $constraints = [];
        if ($options['required']) {
            $constraints = [
                new NotBlank([
                    'message' => 'password.form.constraint.notBlank',
                ]),
            ];
        }

        $constraints[] = new Length([
            'min' => 6,
            'minMessage' => 'password.form.constraint.length',
            // max length allowed by Symfony for security reasons
            'max' => 4096,
        ]);

        if ($options['is_new_password']) {
            $firstFieldLabel = 'password.form.newPassword';
        }

        $attributes = ['autocomplete' => 'new-password'];
        if ($options['disabled'] ?? false) {
            $attributes['disabled'] = 'disabled';
        }

        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'attr' => $attributes,
                    'constraints' => $constraints,
                    'label' => $firstFieldLabel,
                    'help' => $options['help'],
                ],
                'second_options' => [
                    'attr' => $attributes,
                    'label' => 'password.form.repeatPassword',
                ],
                'invalid_message' => 'password.form.constraint.passwordMustMatch',
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_new_password' => false,
            'translation_domain' => 'mosparo',
        ]);
    }
}

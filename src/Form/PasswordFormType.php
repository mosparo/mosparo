<?php

namespace Mosparo\Form;

use Mosparo\Helper\PasswordHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class PasswordFormType extends AbstractType
{
    protected PasswordHelper $passwordHelper;

    public function __construct(PasswordHelper $passwordHelper)
    {
        $this->passwordHelper = $passwordHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $minLength = $this->passwordHelper->getMinimumLength();
        $minStrength = $this->passwordHelper->getMinimumStrength();
        $blockCompromised = $this->passwordHelper->blockCompromisedPasswords();

        $firstFieldLabel = 'password.form.password';
        $constraints = $options['constraints'] ?? [];
        if ($options['required']) {
            $constraints[] = new NotBlank(
                message: 'password.form.constraint.notBlank',
            );
        }

        $constraints[] = new Length(
            min: $minLength,
            minMessage: 'password.form.constraint.length',
            // max length allowed by Symfony for security reasons
            max: 4096,
        );

        if ($minStrength) {
            $constraints[] = new PasswordStrength(
                minScore: $minStrength,
                message: 'password.form.constraint.tooWeak',
            );
        }

        if ($blockCompromised) {
            $constraints[] = new NotCompromisedPassword(
                message: 'password.form.constraint.compromised',
                skipOnError: true,
            );
        }

        if ($options['is_new_password']) {
            $firstFieldLabel = 'password.form.newPassword';
        }

        $attributes = ['autocomplete' => 'new-password'];
        if ($options['disabled'] ?? false) {
            $attributes['disabled'] = 'disabled';
        }

        $strengthAttributes = [
            'data-min-length' => $minLength,
            'data-min-strength' => $minStrength,
        ];

        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'attr' => $attributes + $strengthAttributes,
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
            'is_new_user' => false,
            'is_new_password' => false,
            'translation_domain' => 'mosparo',
        ]);
    }
}

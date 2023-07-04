<?php

namespace Mosparo\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DesignSettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $boxSizeChoices = [
            'settings.design.choices.boxSize.small' => 'small',
            'settings.design.choices.boxSize.medium' => 'medium',
            'settings.design.choices.boxSize.large' => 'large',
        ];

        if ($options['mode'] === 'advanced') {
            $builder
                ->add('boxSize', ChoiceType::class, ['label' => 'settings.design.form.boxSize', 'expanded' => true, 'choices' => $boxSizeChoices])
                ->add('boxRadius', IntegerType::class, ['label' => 'settings.design.form.boxRadius', 'attr' => ['class' => 'text-end', 'min' => 0, 'data-variable' => '--mosparo-border-radius', 'autocomplete' => 'off']])
                ->add('boxBorderWidth', IntegerType::class, ['label' => 'settings.design.form.boxBorderWidth', 'attr' => ['class' => 'text-end', 'min' => 0, 'max' => 20, 'data-variable' => '--mosparo-border-width', 'autocomplete' => 'off']])
                ->add('colorBackground', TextType::class, ['label' => 'settings.design.form.color.background', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-background-color', 'data-contrast-value' => 'background', 'autocomplete' => 'off']])
                ->add('colorBorder', TextType::class, ['label' => 'settings.design.form.color.border', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-border-color', 'autocomplete' => 'off']])
                ->add('colorCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-circle-border-color', 'data-colorpicker-allow-empty' => 'false', 'autocomplete' => 'off']])
                ->add('colorText', TextType::class, ['label' => 'settings.design.form.color.text', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-text-color', 'data-colorpicker-allow-empty' => 'false', 'data-colorpicker-allow-alpha-value' => 'false', 'data-contrast-value' => 'text', 'autocomplete' => 'off']])
                ->add('colorShadow', TextType::class, ['label' => 'settings.design.form.color.shadow', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-shadow-color', 'autocomplete' => 'off']])
                ->add('colorShadowInset', TextType::class, ['label' => 'settings.design.form.color.shadowInset', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-shadow-inset-color', 'autocomplete' => 'off']])
                ->add('colorFocusCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-focus-circle-border-color', 'data-colorpicker-allow-empty' => 'false', 'autocomplete' => 'off']])
                ->add('colorFocusCheckboxShadow', TextType::class, ['label' => 'settings.design.form.color.checkboxShadow', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-focus-circle-shadow-color', 'autocomplete' => 'off']])
                ->add('colorLoadingCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-loading-circle-border-color', 'autocomplete' => 'off']])
                ->add('colorLoadingCheckboxAnimatedCircle', TextType::class, ['label' => 'settings.design.form.color.checkboxAnimatedCircle', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-loading-circle-animated-border-color', 'data-colorpicker-allow-empty' => 'false', 'autocomplete' => 'off']])
                ->add('colorSuccessBackground', TextType::class, ['label' => 'settings.design.form.color.background', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-background-color', 'data-contrast-value' => 'background', 'autocomplete' => 'off']])
                ->add('colorSuccessBorder', TextType::class, ['label' => 'settings.design.form.color.border', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-border-color', 'autocomplete' => 'off']])
                ->add('colorSuccessCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-circle-border-color', 'data-colorpicker-allow-empty' => 'false', 'autocomplete' => 'off']])
                ->add('colorSuccessText', TextType::class, ['label' => 'settings.design.form.color.text', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-text-color', 'data-colorpicker-allow-empty' => 'false', 'data-colorpicker-allow-alpha-value' => 'false', 'data-contrast-value' => 'text', 'autocomplete' => 'off']])
                ->add('colorSuccessShadow', TextType::class, ['label' => 'settings.design.form.color.shadow', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-shadow-color', 'autocomplete' => 'off']])
                ->add('colorSuccessShadowInset', TextType::class, ['label' => 'settings.design.form.color.shadowInset', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-shadow-inset-color', 'autocomplete' => 'off']])
                ->add('colorFailureBackground', TextType::class, ['label' => 'settings.design.form.color.background', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-background-color', 'data-contrast-value' => 'background', 'autocomplete' => 'off']])
                ->add('colorFailureBorder', TextType::class, ['label' => 'settings.design.form.color.border', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-border-color', 'autocomplete' => 'off']])
                ->add('colorFailureCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-circle-border-color', 'data-colorpicker-allow-empty' => 'false', 'autocomplete' => 'off']])
                ->add('colorFailureText', TextType::class, ['label' => 'settings.design.form.color.text', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-text-color', 'data-colorpicker-allow-empty' => 'false', 'data-colorpicker-allow-alpha-value' => 'false', 'data-contrast-value' => 'text', 'autocomplete' => 'off']])
                ->add('colorFailureTextError', TextType::class, ['label' => 'settings.design.form.color.textError', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-text-error-color', 'data-colorpicker-allow-empty' => 'false', 'data-colorpicker-allow-alpha-value' => 'false', 'data-contrast-value' => 'error-text', 'autocomplete' => 'off']])
                ->add('colorFailureShadow', TextType::class, ['label' => 'settings.design.form.color.shadow', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-shadow-color', 'autocomplete' => 'off']])
                ->add('colorFailureShadowInset', TextType::class, ['label' => 'settings.design.form.color.shadowInset', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-shadow-inset-color', 'autocomplete' => 'off']])
                ->add('showPingAnimation', CheckboxType::class, ['label' => 'settings.design.form.showPingAnimation', 'required' => false, 'attr' => ['data-variable' => '--mosparo-ping-animation-name', 'data-variable-value' => 'mosparo__ping-animation', 'data-disabled-variable-value' => 'none']])
                ->add('showMosparoLogo', CheckboxType::class, ['label' => 'settings.design.form.showMosparoLogo', 'required' => false, 'attr' => ['data-variable' => '--mosparo-show-logo', 'data-variable-value' => 'block', 'data-disabled-variable-value' => 'none']])
                ->add('colorPageBodyBackground', TextType::class, ['label' => 'settings.design.preview.background', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--page-body-background', 'autocomplete' => 'off']])
            ;
        } elseif ($options['mode'] === 'invisible-simple') {
            $builder
                ->add('colorPageBodyBackground', TextType::class, [
                    'label' => 'settings.design.preview.background',
                    'required' => false,
                    'attr' => [
                        'class' => 'colorpicker',
                        'data-variable' => '--page-body-background',
                        'autocomplete' => 'off'
                    ]
                ])
                ->add('colorLoaderBackground', TextType::class, [
                    'label' => 'settings.design.form.color.loaderBackground',
                    'help' => 'settings.design.form.help.loaderBackground',
                    'required' => false,
                    'attr' => [
                        'class' => 'colorpicker',
                        'data-variable' => '--mosparo-loader-background-color',
                        'data-colorpicker-allow-empty' => 'false',
                    ]
                ])
                ->add('colorLoaderText', TextType::class, [
                    'label' => 'settings.design.form.color.loaderText',
                    'help' => 'settings.design.form.help.loaderText',
                    'required' => false,
                    'attr' => [
                        'class' => 'colorpicker',
                        'data-variable' => '--mosparo-loader-text-color',
                        'data-colorpicker-allow-empty' => 'false',
                    ]
                ])
                ->add('colorLoaderCircle', TextType::class, [
                    'label' => 'settings.design.form.color.loaderCircle',
                    'help' => 'settings.design.form.help.loaderCircle',
                    'required' => false,
                    'attr' => [
                        'class' => 'colorpicker',
                        'data-variable' => '--mosparo-loader-circle-color',
                        'data-colorpicker-allow-empty' => 'false',
                    ]
                ])
                ->add('colorFailureTextError', TextType::class, [
                    'label' => 'settings.design.form.color.textError',
                    'help' => 'settings.design.form.help.textError',
                    'required' => false,
                    'attr' => [
                        'class' => 'colorpicker',
                        'data-variable' => '--mosparo-failure-text-error-color',
                        'data-colorpicker-allow-empty' => 'false',
                        'data-colorpicker-allow-alpha-value' => 'false',
                        'data-contrast-value' => 'error-text',
                        'autocomplete' => 'off'
                    ]
                ])
                ->add('showMosparoLogo', CheckboxType::class, [
                    'label' => 'settings.design.form.showMosparoLogo',
                    'help' => 'settings.design.form.help.showMosparoLogo',
                    'required' => false,
                    'attr' => [
                        'data-variable' => '--mosparo-show-logo',
                        'data-variable-value' => 'block',
                        'data-disabled-variable-value' => 'none'
                    ]
                ])
                ->add('fullPageOverlay', CheckboxType::class, [
                    'label' => 'settings.design.form.fullPageOverlay',
                    'help' => 'settings.design.form.help.fullPageOverlay',
                    'required' => false,
                    'attr' => [
                        'data-disabled-variable-value' => 'none'
                    ]
                ])
            ;
        } else {
            $builder
                ->add('boxSize', ChoiceType::class, [
                    'label' => 'settings.design.form.boxSize',
                    'help' => 'settings.design.form.help.boxSize',
                    'expanded' => true,
                    'choices' => $boxSizeChoices
                ])
                ->add('colorWebsiteBackground', TextType::class, [
                    'label' => 'settings.design.form.color.websiteBackground',
                    'help' => 'settings.design.form.help.websiteBackground',
                    'required' => false,
                    'attr' => [
                        'class' => 'colorpicker',
                        'data-contrast-value' => 'website-background',
                        'data-colorpicker-allow-empty' => 'false',
                        'data-colorpicker-allow-alpha-value' => 'false'
                    ]
                ])
                ->add('colorWebsiteForeground', TextType::class, [
                    'label' => 'settings.design.form.color.websiteForeground',
                    'help' => 'settings.design.form.help.websiteForeground',
                    'required' => false,
                    'attr' => [
                        'class' => 'colorpicker',
                        'data-contrast-value' => 'website-foreground',
                        'data-colorpicker-allow-empty' => 'false',
                        'data-colorpicker-allow-alpha-value' => 'false'
                    ]
                ])
                ->add('colorWebsiteAccent', TextType::class, [
                    'label' => 'settings.design.form.color.websiteAccent',
                    'help' => 'settings.design.form.help.websiteAccent',
                    'required' => false,
                    'attr' => [
                        'class' => 'colorpicker',
                        'data-contrast-value' => 'website-accent',
                        'data-colorpicker-allow-empty' => 'false',
                        'data-colorpicker-allow-alpha-value' => 'false'
                    ]
                ])
                ->add('colorHover', HiddenType::class, [
                    'required' => false,
                ])
                ->add('colorSuccess', HiddenType::class, [
                    'required' => false,
                ])
                ->add('colorFailure', HiddenType::class, [
                    'required' => false,
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'mosparo',
            'mode' => 'simple',
        ]);
    }
}

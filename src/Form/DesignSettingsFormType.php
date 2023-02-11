<?php

namespace Mosparo\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
        $builder
            ->add('boxSize', ChoiceType::class, ['label' => 'settings.design.form.boxSize', 'expanded' => true, 'choices' => $boxSizeChoices])
            ->add('boxRadius', IntegerType::class, ['label' => 'settings.design.form.boxRadius', 'attr' => ['class' => 'text-end', 'min' => 0, 'data-variable' => '--mosparo-border-radius']])
            ->add('boxBorderWidth', IntegerType::class, ['label' => 'settings.design.form.boxBorderWidth', 'attr' => ['class' => 'text-end', 'min' => 0, 'max' => 20, 'data-variable' => '--mosparo-border-width']])
            ->add('colorBackground', TextType::class, ['label' => 'settings.design.form.color.background', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-background-color', 'data-contrast-value' => 'background']])
            ->add('colorBorder', TextType::class, ['label' => 'settings.design.form.color.border', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-border-color']])
            ->add('colorCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-circle-border-color']])
            ->add('colorText', TextType::class, ['label' => 'settings.design.form.color.text', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-text-color', 'data-colorpicker-allow-alpha-value' => 'false', 'data-contrast-value' => 'text']])
            ->add('colorShadow', TextType::class, ['label' => 'settings.design.form.color.shadow', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-shadow-color']])
            ->add('colorShadowInset', TextType::class, ['label' => 'settings.design.form.color.shadowInset', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-shadow-inset-color']])
            ->add('colorFocusCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-focus-circle-border-color']])
            ->add('colorFocusCheckboxShadow', TextType::class, ['label' => 'settings.design.form.color.checkboxShadow', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-focus-circle-shadow-color']])
            ->add('colorLoadingCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-loading-circle-border-color']])
            ->add('colorLoadingCheckboxAnimatedCircle', TextType::class, ['label' => 'settings.design.form.color.checkboxAnimatedCircle', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-loading-circle-animated-border-color']])
            ->add('colorSuccessBackground', TextType::class, ['label' => 'settings.design.form.color.background', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-background-color', 'data-contrast-value' => 'background']])
            ->add('colorSuccessBorder', TextType::class, ['label' => 'settings.design.form.color.border', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-border-color']])
            ->add('colorSuccessCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-circle-border-color']])
            ->add('colorSuccessText', TextType::class, ['label' => 'settings.design.form.color.text', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-text-color', 'data-colorpicker-allow-alpha-value' => 'false', 'data-contrast-value' => 'text']])
            ->add('colorSuccessShadow', TextType::class, ['label' => 'settings.design.form.color.shadow', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-shadow-color']])
            ->add('colorSuccessShadowInset', TextType::class, ['label' => 'settings.design.form.color.shadowInset', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-shadow-inset-color']])
            ->add('colorFailureBackground', TextType::class, ['label' => 'settings.design.form.color.background', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-background-color', 'data-contrast-value' => 'background']])
            ->add('colorFailureBorder', TextType::class, ['label' => 'settings.design.form.color.border', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-border-color']])
            ->add('colorFailureCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-circle-border-color']])
            ->add('colorFailureText', TextType::class, ['label' => 'settings.design.form.color.text', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-text-color', 'data-colorpicker-allow-alpha-value' => 'false', 'data-contrast-value' => 'text']])
            ->add('colorFailureTextError', TextType::class, ['label' => 'settings.design.form.color.textError', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-text-error-color', 'data-colorpicker-allow-alpha-value' => 'false', 'data-contrast-value' => 'error-text']])
            ->add('colorFailureShadow', TextType::class, ['label' => 'settings.design.form.color.shadow', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-shadow-color']])
            ->add('colorFailureShadowInset', TextType::class, ['label' => 'settings.design.form.color.shadowInset', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-shadow-inset-color']])
            ->add('showPingAnimation', CheckboxType::class, ['label' => 'settings.design.form.showPingAnimation', 'required' => false, 'attr' => ['data-variable' => '--mosparo-ping-animation-name', 'data-variable-value' => 'mosparo__ping-animation', 'data-disabled-variable-value' => 'none']])
            ->add('showMosparoLogo', CheckboxType::class, ['label' => 'settings.design.form.showMosparoLogo', 'required' => false, 'attr' => ['data-variable' => '--mosparo-show-logo', 'data-variable-value' => 'block', 'data-disabled-variable-value' => 'none']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'mosparo',
        ]);
    }
}

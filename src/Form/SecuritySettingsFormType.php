<?php

namespace Mosparo\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class SecuritySettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isGeneralSettings = $options['isGeneralSettings'];
        $addOverrideOptions = $options['addOverrideOptions'];

        $builder
            // Minimum time
            ->add('minimumTimeActive', CheckboxType::class, ['label' => 'settings.security.form.minimumTimeActive', 'required' => false, 'attr' => ['class' => 'card-field-switch']])
            ->add('minimumTimeSeconds', IntegerType::class, ['label' => 'settings.security.form.minimumTimeSeconds', 'help' => 'settings.security.form.minimumTimeSecondsHelp', 'required' => false])

            // Honeypot
            ->add('honeypotFieldActive', CheckboxType::class, ['label' => 'settings.security.form.honeypotFieldActive', 'required' => false, 'attr' => ['class' => 'card-field-switch']])
            ->add('honeypotFieldName', TextType::class, ['label' => 'settings.security.form.honeypotFieldName', 'help' => 'settings.security.form.honeypotFieldNameHelp', 'required' => false, 'constraints' => [
                new Regex([
                    'pattern' => '/^[a-z0-9\-]*$/i',
                    'message' => 'honeypotField.nameHasInvalidCharacter',
                ])
            ]])

            // delay
            ->add('delayActive', CheckboxType::class, ['label' => 'settings.security.form.delayActive', 'required' => false, 'attr' => ['class' => 'card-field-switch']])
            ->add('delayNumberOfRequests', IntegerType::class, ['label' => 'settings.security.form.delayNumberOfAllowedRequests', 'help' => 'settings.security.form.delayNumberOfAllowedRequestsHelp'])
            ->add('delayDetectionTimeFrame', IntegerType::class, ['label' => 'settings.security.form.delayDetectionTimeFrame', 'help' => 'settings.security.form.delayDetectionTimeFrameHelp'])
            ->add('delayTime', IntegerType::class, ['label' => 'settings.security.form.delayTime', 'help' => 'settings.security.form.delayTimeHelp'])
            ->add('delayMultiplicator', NumberType::class, ['label' => 'settings.security.form.delayMultiplicator', 'help' => 'settings.security.form.delayMultiplicatorHelp', 'html5' => true, 'scale' => 1, 'attr' => ['min' => 0.1, 'step' => 'any']])

            // lockout
            ->add('lockoutActive', CheckboxType::class, ['label' => 'settings.security.form.lockoutActive', 'required' => false, 'attr' => ['class' => 'card-field-switch']])
            ->add('lockoutNumberOfRequests', IntegerType::class, ['label' => 'settings.security.form.lockoutNumberOfAllowedRequests', 'help' => 'settings.security.form.lockoutNumberOfAllowedRequestsHelp'])
            ->add('lockoutDetectionTimeFrame', IntegerType::class, ['label' => 'settings.security.form.lockoutDetectionTimeFrame', 'help' => 'settings.security.form.lockoutDetectionTimeFrameHelp'])
            ->add('lockoutTime', IntegerType::class, ['label' => 'settings.security.form.lockoutTime', 'help' => 'settings.security.form.lockoutTimeHelp'])
            ->add('lockoutMultiplicator', NumberType::class, ['label' => 'settings.security.form.lockoutMultiplicator', 'help' => 'settings.security.form.lockoutMultiplicatorHelp', 'html5' => true, 'scale' => 1, 'attr' => ['min' => 0.1, 'step' => 'any']])
        ;

        if ($isGeneralSettings) {
            $builder
                ->add('ipAllowList', TextareaType::class, ['label' => 'settings.security.form.ipAllowList', 'required' => false, 'help' => 'settings.security.form.ipAllowListHelp', 'attr' => ['class' => 'ip-address-field']]);
        }

        if ($addOverrideOptions) {
            $builder
                ->add('overrideMinimumTime', CheckboxType::class, ['label' => 'settings.security.form.overrideMinimumTime', 'required' => false, 'attr' => ['class' => 'full-card-field-switch']])
                ->add('overrideHoneypotField', CheckboxType::class, ['label' => 'settings.security.form.overrideHoneypotField', 'required' => false, 'attr' => ['class' => 'full-card-field-switch']])
                ->add('overrideDelay', CheckboxType::class, ['label' => 'settings.security.form.overrideDelay', 'required' => false, 'attr' => ['class' => 'full-card-field-switch']])
                ->add('overrideLockout', CheckboxType::class, ['label' => 'settings.security.form.overrideLockout', 'required' => false, 'attr' => ['class' => 'full-card-field-switch']]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'isGeneralSettings' => false,
            'addOverrideOptions' => false,
            'translation_domain' => 'mosparo',
        ]);
    }
}

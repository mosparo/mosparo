<?php

namespace Mosparo\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class SecuritySettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isGeneralSettings = $options['isGeneralSettings'];
        $addOverrideOptions = $options['addOverrideOptions'];

        $builder
            // Minimum time
            ->add('minimumTimeActive', CheckboxType::class, ['label' => 'settings.security.form.minimumTimeActive', 'required' => false, 'attr' => ['class' => 'card-field-switch']])
            ->add('minimumTimeSeconds', IntegerType::class, ['label' => 'settings.security.form.minimumTimeSeconds', 'help' => 'unitHelp.inSeconds', 'required' => false])

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
            ->add('delayDetectionTimeFrame', IntegerType::class, ['label' => 'settings.security.form.delayDetectionTimeFrame', 'help' => 'unitHelp.inSeconds'])
            ->add('delayTime', IntegerType::class, ['label' => 'settings.security.form.delayTime', 'help' => 'unitHelp.inSeconds'])
            ->add('delayMultiplicator', NumberType::class, ['label' => 'settings.security.form.delayMultiplicator', 'help' => 'settings.security.form.delayMultiplicatorHelp', 'html5' => true, 'scale' => 1, 'attr' => ['min' => 0.1, 'step' => 'any']])

            // lockout
            ->add('lockoutActive', CheckboxType::class, ['label' => 'settings.security.form.lockoutActive', 'required' => false, 'attr' => ['class' => 'card-field-switch']])
            ->add('lockoutNumberOfRequests', IntegerType::class, ['label' => 'settings.security.form.lockoutNumberOfAllowedRequests', 'help' => 'settings.security.form.lockoutNumberOfAllowedRequestsHelp'])
            ->add('lockoutDetectionTimeFrame', IntegerType::class, ['label' => 'settings.security.form.lockoutDetectionTimeFrame', 'help' => 'unitHelp.inSeconds'])
            ->add('lockoutTime', IntegerType::class, ['label' => 'settings.security.form.lockoutTime', 'help' => 'unitHelp.inSeconds'])
            ->add('lockoutMultiplicator', NumberType::class, ['label' => 'settings.security.form.lockoutMultiplicator', 'help' => 'settings.security.form.lockoutMultiplicatorHelp', 'html5' => true, 'scale' => 1, 'attr' => ['min' => 0.1, 'step' => 'any']])

            // Proof of work
            ->add('proofOfWorkActive', CheckboxType::class, ['label' => 'settings.security.form.proofOfWorkActive', 'required' => false, 'attr' => ['class' => 'card-field-switch proof-of-work-active']])
            ->add('proofOfWorkComplexity', IntegerType::class, ['label' => 'settings.security.form.proofOfWorkComplexity', 'required' => false, 'attr' => ['class' => 'text-center complexity-field', 'min' => 2, 'max' => 7]])
            ->add('proofOfWorkDynamicComplexityActive', CheckboxType::class, ['label' => 'settings.security.form.proofOfWorkDynamicComplexityActive', 'required' => false, 'attr' => ['class' => 'sub-card-field-switch']])
            ->add('proofOfWorkDynamicComplexityMaxComplexity', IntegerType::class, ['label' => 'settings.security.form.proofOfWorkDynamicComplexityMaxComplexity', 'required' => false, 'attr' => ['class' => 'text-center complexity-field', 'min' => 2, 'max' => 7]])
            ->add('proofOfWorkDynamicComplexityNumberOfSubmissions', IntegerType::class, ['label' => 'settings.security.form.proofOfWorkDynamicComplexityNumberOfSubmissions', 'required' => false, 'help' => 'settings.security.form.proofOfWorkDynamicComplexityNumberOfSubmissionsHelp'])
            ->add('proofOfWorkDynamicComplexityTimeFrame', IntegerType::class, ['label' => 'settings.security.form.proofOfWorkDynamicComplexityTimeFrame', 'required' => false, 'help' => 'unitHelp.inSeconds'])
            ->add('proofOfWorkDynamicComplexityBasedOnIpAddress', CheckboxType::class, ['label' => 'settings.security.form.proofOfWorkDynamicComplexityBasedOnIpAddress', 'help' => 'settings.security.form.proofOfWorkDynamicComplexityBasedOnIpAddressHelp', 'required' => false, ])

            // equal submissions
            ->add('equalSubmissionsActive', CheckboxType::class, ['label' => 'settings.security.form.equalSubmissionsActive', 'required' => false, 'attr' => ['class' => 'card-field-switch']])
            ->add('equalSubmissionsNumberOfEqualSubmissions', IntegerType::class, ['label' => 'settings.security.form.equalSubmissionsNumberOfEqualSubmissions', 'help' => 'settings.security.form.equalSubmissionsNumberOfEqualSubmissionsHelp'])
            ->add('equalSubmissionsTimeFrame', IntegerType::class, ['label' => 'settings.security.form.equalSubmissionsTimeFrame', 'help' => 'unitHelp.inSeconds'])
            ->add('equalSubmissionsBasedOnIpAddress', CheckboxType::class, ['label' => 'settings.security.form.equalSubmissionsBasedOnIpAddress', 'help' => 'settings.security.form.equalSubmissionsBasedOnIpAddressHelp', 'required' => false, ])
        ;

        if ($isGeneralSettings) {
            $builder
                ->add('ipAllowList', TextareaType::class, ['label' => 'settings.security.form.ipAllowList', 'required' => false, 'help' => 'settings.security.form.ipAllowListHelp', 'attr' => ['class' => 'ip-address-field']]);
        } else {
            $builder
                ->add('overrideSpamDetection', CheckboxType::class, ['label' => 'settings.security.form.overrideSpamDetection', 'required' => false, 'attr' => ['class' => 'full-card-field-switch']])
                ->add('spamStatus', ChoiceType::class, [
                    'label' => 'project.form.status',
                    'attr' => ['class' => 'form-select'],
                    'choices' => ['state.inactive' => 0, 'state.active' => 1],
                    'help' => 'project.form.statusHelp',
                ])
                ->add('spamScore', NumberType::class, [
                    'label' => 'project.form.spamScore',
                    'help' => 'project.form.spamScoreHelp',
                    'html5' => true,
                    'scale' => 1,
                    'attr' => [
                        'min' => 0.1,
                        'step' => 'any',
                    ],
                ]);
        }

        if ($addOverrideOptions) {
            $builder
                ->add('overrideMinimumTime', CheckboxType::class, ['label' => 'settings.security.form.overrideMinimumTime', 'required' => false, 'attr' => ['class' => 'full-card-field-switch']])
                ->add('overrideHoneypotField', CheckboxType::class, ['label' => 'settings.security.form.overrideHoneypotField', 'required' => false, 'attr' => ['class' => 'full-card-field-switch']])
                ->add('overrideDelay', CheckboxType::class, ['label' => 'settings.security.form.overrideDelay', 'required' => false, 'attr' => ['class' => 'full-card-field-switch']])
                ->add('overrideLockout', CheckboxType::class, ['label' => 'settings.security.form.overrideLockout', 'required' => false, 'attr' => ['class' => 'full-card-field-switch']])
                ->add('overrideProofOfWork', CheckboxType::class, ['label' => 'settings.security.form.overrideProofOfWork', 'required' => false, 'attr' => ['class' => 'full-card-field-switch']])
                ->add('overrideEqualSubmissions', CheckboxType::class, ['label' => 'settings.security.form.overrideEqualSubmissions', 'required' => false, 'attr' => ['class' => 'full-card-field-switch']]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'isGeneralSettings' => false,
            'addOverrideOptions' => false,
            'translation_domain' => 'mosparo',
        ]);
    }
}

<?php

namespace Mosparo\Form;

use Mosparo\Rules\SubmissionRule\SubmissionRuleInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubmissionRuleConfigValueFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $submissionRule = $options['submissionRule'] ?? null;
        if (!$submissionRule || !($submissionRule instanceof SubmissionRuleInterface)) {
            return;
        }

        $submissionRule->addSettingsFormFields($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'submissionRule' => null,
            'translation_domain' => 'mosparo',
        ]);
    }
}

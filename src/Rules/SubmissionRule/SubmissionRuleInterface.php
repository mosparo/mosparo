<?php

namespace Mosparo\Rules\SubmissionRule;

use Mosparo\Entity\Submission;
use Mosparo\Entity\SubmissionRule;
use Symfony\Component\Form\FormBuilderInterface;

interface SubmissionRuleInterface
{
    public function getKey(): string;
    public function getName(): string;
    public function getSummary(): string;
    public function getDescription(): string;
    public function getDefaultRating(): float;
    public function setDefaultSettings(SubmissionRule $submissionRule): void;
    public function addSettingsFormFields(FormBuilderInterface $formBuilder): void;
    public function checkSubmission(SubmissionRule $storedSubmissionRule, Submission $submission): void;
}
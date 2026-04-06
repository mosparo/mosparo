<?php

namespace Mosparo\Rules\SubmissionRule\Rules;

use Mosparo\Entity\Submission;
use Mosparo\Entity\SubmissionRule;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class RandomValueSubmissionRule extends AbstractSubmissionRule
{
    protected string $key = 'random-values';
    protected string $name = 'submissionRule.randomValues.title';
    protected string $summary = 'submissionRule.randomValues.summary';
    protected string $description = 'submissionRule.randomValues.description';
    protected float $defaultRating = 20;

    public function setDefaultSettings(SubmissionRule $submissionRule): void
    {
        $submissionRule
            ->setConfigValues([
                'numberOfMatchingFields' => 3,
                'matchTextarea' => false,
                'numberOfRandomCharacters' => 15,
                'requireBothCases' => true,
            ])
            ->setRating($this->getDefaultRating())
        ;
    }

    public function addSettingsFormFields(FormBuilderInterface $formBuilder): void
    {
        $formBuilder
            ->add('numberOfMatchingFields', NumberType::class, [
                'label' => 'submissionRule.randomValues.field.numberOfMatchingFields',
                'help' => 'submissionRule.randomValues.field.numberOfMatchingFieldsHelp',
            ])
            ->add('matchTextarea', CheckboxType::class, [
                'label' => 'submissionRule.randomValues.field.matchTextarea',
                'help' => 'submissionRule.randomValues.field.matchTextareaHelp',
                'required' => false,
            ])
            ->add('numberOfRandomCharacters', NumberType::class, [
                'label' => 'submissionRule.randomValues.field.numberOfRandomCharacters',
                'help' => 'submissionRule.randomValues.field.numberOfRandomCharactersHelp',
            ])
            ->add('requireBothCases', CheckboxType::class, [
                'label' => 'submissionRule.randomValues.field.requireBothCases',
                'help' => 'submissionRule.randomValues.field.requireBothCasesHelp',
                'required' => false,
            ])
        ;
    }

    public function checkSubmission(SubmissionRule $storedSubmissionRule, Submission $submission): void
    {
        $numberOfRequiredMatchingFields = intval($storedSubmissionRule->getConfigValue('numberOfMatchingFields') ?? 3);
        $matchTextarea = boolval($storedSubmissionRule->getConfigValue('matchTextarea') ?? false);
        $numberOfRandomCharacters = intval($storedSubmissionRule->getConfigValue('numberOfRandomCharacters') ?? 3);
        $requireBothCases = boolval($storedSubmissionRule->getConfigValue('requireBothCases') ?? true);

        $numberOfMatchingFields = 0;
        $matchingFields = [];
        $matches = [];
        foreach ($submission->getData() as $groupKey => $groupData) {
            if ($groupKey !== 'formData') {
                continue;
            }

            foreach ($groupData as $fieldData) {
                $isTextField = str_starts_with($fieldData['fieldPath'], 'input[text].');
                $isTextarea = str_starts_with($fieldData['fieldPath'], 'textarea.');
                if ($isTextField || ($matchTextarea && $isTextarea)) {
                    $match = null;
                    if ($isTextField && preg_match('/^[A-Za-z0-9]{' . $numberOfRandomCharacters . ',}$/', trim($fieldData['value']), $fieldMatches)) {
                        $match = current($fieldMatches);
                    } else if ($isTextarea && preg_match('/(\W|^)[A-Za-z0-9]{' . $numberOfRandomCharacters . ',}(\W|$)/', trim($fieldData['value']), $fieldMatches)) {
                        $match = current($fieldMatches);
                    }

                    if ($match && $this->verifyMatch($match, $requireBothCases)) {
                        $numberOfMatchingFields++;
                        $matchingFields[] = $groupKey . '.' . $fieldData['fieldPath'];
                        $matches[] = current($fieldMatches);
                    }
                }
            }
        }

        if ($numberOfMatchingFields >= $numberOfRequiredMatchingFields) {
            $submission->getDetectionResult()->addMatchedSubmissionRule($this->getKey(), [
                'identifier' => $this->getKey(),
                'rating' => $storedSubmissionRule->getRating(),
                'matchingFields' => $matchingFields,
                'matches' => $matches,
            ]);
        }
    }

    protected function verifyMatch(string $match, bool $requireBothCases): bool
    {
        // If the match is a long number, we do not care.
        if (is_numeric($match)) {
            return false;
        }

        // If the match is only lowercase OR uppercase characters (maybe together with numbers), we do not care.
        if ($requireBothCases && (strtolower($match) === $match || strtoupper($match) === $match)) {
            return false;
        }

        return true;
    }
}
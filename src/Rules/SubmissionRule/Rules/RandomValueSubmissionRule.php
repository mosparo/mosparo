<?php

namespace Mosparo\Rules\SubmissionRule\Rules;

use Mosparo\Entity\Submission;
use Mosparo\Entity\SubmissionRule;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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

    public function addSettingsFormFields(FormBuilderInterface $formBuilder, bool $disabled): void
    {
        $formBuilder
            ->add('numberOfMatchingFields', IntegerType::class, [
                'label' => 'submissionRule.randomValues.field.numberOfMatchingFields',
                'help' => 'submissionRule.randomValues.field.numberOfMatchingFieldsHelp',
                'attr' => ['class' => 'text-end', 'min' => 1, 'autocomplete' => 'off', 'disabled' => $disabled],
            ])
            ->add('matchTextarea', CheckboxType::class, [
                'label' => 'submissionRule.randomValues.field.matchTextarea',
                'help' => 'submissionRule.randomValues.field.matchTextareaHelp',
                'required' => false,
                'attr' => ['disabled' => $disabled],
            ])
            ->add('numberOfRandomCharacters', IntegerType::class, [
                'label' => 'submissionRule.randomValues.field.numberOfRandomCharacters',
                'help' => 'submissionRule.randomValues.field.numberOfRandomCharactersHelp',
                'attr' => ['class' => 'text-end', 'min' => 5, 'autocomplete' => 'off', 'disabled' => $disabled],
            ])
            ->add('requireBothCases', CheckboxType::class, [
                'label' => 'submissionRule.randomValues.field.requireBothCases',
                'help' => 'submissionRule.randomValues.field.requireBothCasesHelp',
                'required' => false,
                'attr' => ['disabled' => $disabled],
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
                    foreach ((is_array($fieldData['value']) ? $fieldData['value'] : [$fieldData['value']]) as $value) {
                        if ($value === null || !trim($value)) {
                            continue;
                        }

                        [$match, $fieldMatch] = $this->checkIfFieldMatches($value, $isTextField, $isTextarea, $numberOfRandomCharacters, $requireBothCases);

                        if ($match) {
                            $numberOfMatchingFields++;
                            $matchingFields[$groupKey . '.' . $fieldData['fieldPath']] = 1;
                            $matches[] = $fieldMatch;
                        }
                    }
                }
            }
        }

        if ($numberOfMatchingFields >= $numberOfRequiredMatchingFields) {
            $submission->getDetectionResult()->addMatchedSubmissionRule($this->getKey(), [
                'identifier' => $this->getKey(),
                'rating' => $storedSubmissionRule->getRating(),
                'matchingFields' => array_keys($matchingFields),
                'matches' => $matches,
            ]);
        }
    }

    protected function checkIfFieldMatches(string $value, bool $isTextField, bool $isTextarea, int $numberOfRandomCharacters, bool $requireBothCases): array
    {
        if ($isTextField && preg_match('/^[A-Za-z0-9]{' . $numberOfRandomCharacters . ',}$/', trim($value), $fieldMatches)) {
            $fieldMatch = current($fieldMatches);
            if ($fieldMatch && $this->verifyMatch($fieldMatch, $requireBothCases)) {
                return [true, $fieldMatch];
            }
        } else if ($isTextarea && preg_match_all('/\b[A-Za-z0-9]{' . $numberOfRandomCharacters . ',}\b/', $value, $fieldMatches)) {
            foreach ($fieldMatches[0] as $textareaMatch) {
                if ($this->verifyMatch($textareaMatch, $requireBothCases)) {
                    return [true, $textareaMatch];
                }
            }
        }

        return [false, null];
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
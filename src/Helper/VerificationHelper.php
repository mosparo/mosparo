<?php

namespace Mosparo\Helper;

use Mosparo\Entity\Submission;

class VerificationHelper
{
    /**
     * Verifies the form data. Returns an array with the result.
     *
     * @param \Mosparo\Entity\Submission $submission
     * @param array $formData
     * @return array
     */
    public function verifyFormData(Submission $submission, array $formData): array
    {
        $issues = [];
        $submissionData = $submission->getData();
        $submittedFormData = $submissionData['formData'];

        if (empty($submittedFormData) || empty($formData)) {
            $submission->addIssue([
                'name' => 'formData',
                'message' => 'Form data is empty.',
            ]);
        }

        foreach ($submittedFormData as $sFieldData) {
            if (isset($sFieldData['type']) && $sFieldData['type'] === 'honeypot') {
                continue;
            }

            $sKey = $sFieldData['name'];
            $sValue = $sFieldData['value'] ?? '';

            if (!isset($formData[$sKey])) {
                $submission->addIssue([
                    'name' => $sKey,
                    'message' => 'Missing in form data, verification not possible.',
                    'debugInformation' => [
                        'reason' => 'field_not_in_received_data',
                        'expectedValue' => $this->describeValue($sValue),
                    ]
                ]);

                $submission->setVerifiedField($sKey, Submission::SUBMISSION_FIELD_INVALID);
                continue;
            }

            $fValue = $formData[$sKey];

            if (!$this->verifyValues($sValue, $fValue)) {
                $submission->addIssue([
                    'name' => $sKey,
                    'message' => 'Field not valid.',
                    'debugInformation' => [
                        'reason' => 'field_signature_invalid',
                        'expectedValue' => $this->describeValue($sValue),
                        'receivedValue' => $this->describeValue($fValue, true),
                    ]
                ]);

                $submission->setVerifiedField($sKey, Submission::SUBMISSION_FIELD_INVALID);
            } else {
                $submission->setVerifiedField($sKey, Submission::SUBMISSION_FIELD_VALID);
            }
        }

        return ['valid' => ($submission->countIssues() === 0), 'verifiedFields' => $submission->getVerifiedFields(), 'issues' => $submission->getIssues($submission->getProject()->isApiDebugMode())];
    }

    /**
     * Checks the hash of the given values. Returns true when the values are the same.
     *
     * @param mixed $sValue
     * @param mixed $fValue
     * @return bool
     */
    protected function verifyValues($sValue, $fValue): bool
    {
        if (is_array($sValue) && is_array($fValue)) {
            foreach ($sValue as $sSubKey => $sSubValue) {
                if (is_array($sSubValue) && isset($fValue[$sSubKey])) {
                    $result = $this->verifyValues($sSubValue, $fValue[$sSubKey]);

                    if (!$result) {
                        return false;
                    }
                } else {
                    $sSubValueSig = hash('sha256', $sSubValue);

                    if (!in_array($sSubValueSig, $fValue)) {
                        return false;
                    }
                }
            }
        } else if (!is_array($sValue) && !is_array($fValue)) {
            $sValueSig = hash('sha256', $sValue);

            if ($sValueSig != $fValue) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Brings the value in a explainable format for the API debug mode
     *
     * @param mixed $value
     * @param boolean $valueIsHash
     * @return array
     */
    protected function describeValue($value, $valueIsHash = false): array
    {
        if (is_array($value)) {
            $items = [];
            foreach ($value as $key => $item) {
                $items[$key] = $this->describeValue($item, $valueIsHash);
            }

            return [
                'type' => ($valueIsHash) ? 'hash-array' : 'array',
                'items' => $items,
            ];
        } else {
            $hash = [];
            if (!$valueIsHash && !is_array($value)) {
                $hash = ['hash' => hash('sha256', $value)];
            }

            return [
                'type' => ($valueIsHash) ? 'hash' : gettype($value),
                'value' => $value,
            ] + $hash;
        }
    }
}
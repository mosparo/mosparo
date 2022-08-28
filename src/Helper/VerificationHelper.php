<?php

namespace Mosparo\Helper;

use Mosparo\Entity\Submission;

class VerificationHelper
{
    /**
     * Verifies the form data. Returns an arra with the result.
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
            $issues[] = [
                'name' => 'formData',
                'message' => 'Form data is empty.',
            ];
        }

        foreach ($submittedFormData as $sFieldData) {
            if (isset($sFieldData['type']) && $sFieldData['type'] === 'honeypot') {
                continue;
            }

            $sKey = $sFieldData['name'];
            $sValue = $sFieldData['value'];

            if (!isset($formData[$sKey])) {
                $issues[] = ['name' => $sKey, 'message' => 'Missing in form data, verification not possible.'];
                $submission->setVerifiedField($sKey, Submission::SUBMISSION_FIELD_INVALID);
                continue;
            }

            $fValue = $formData[$sKey];

            if (!$this->verifyValues($sValue, $fValue)) {
                $issues[] = ['name' => $sKey, 'message' => 'Field not valid.'];
                $submission->setVerifiedField($sKey, Submission::SUBMISSION_FIELD_INVALID);
            } else {
                $submission->setVerifiedField($sKey, Submission::SUBMISSION_FIELD_VALID);
            }
        }

        return ['valid' => (count($issues) === 0), 'verifiedFields' => $submission->getVerifiedFields(), 'issues' => $issues];
    }

    /**
     * Checks the signatures of the given values. Returns true when the values are the same.
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
}
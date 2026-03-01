<?php

namespace Mosparo\Trait;

trait SubmissionDataTrait
{
    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function appendData(array $data): self
    {
        foreach ($data as $typeKey => $typeData) {
            if ($typeKey === 'metaData') {
                if (isset($this->data[$typeKey])) {
                    $this->data[$typeKey] = array_replace_recursive($this->data[$typeKey], $typeData);
                } else {
                    $this->data[$typeKey] = $typeData;
                }
            } else {
                $this->mergeFieldData($typeKey, $typeData);
            }
        }

        return $this;
    }

    protected function mergeFieldData(string $key, array $data)
    {
        if (!isset($this->data[$key])) {
            $this->data[$key] = $data;
            return;
        }

        foreach ($data as $fieldData) {
            foreach ($this->data[$key] as $oFieldKey => $oFieldData) {
                if ($fieldData['name'] === $oFieldData['name']) {
                    $this->data[$key][$oFieldKey] = $fieldData;

                    // If we found it, go to the next field
                    continue 2;
                }
            }

            // Add the field if it is not already in the array
            $this->data[$key][] = $fieldData;
        }
    }

    public function getDataValue(string $group, string $name)
    {
        if (!isset($this->data[$group])) {
            return false;
        }

        foreach ($this->data[$group] as $item) {
            if ($item['name'] === $name) {
                return $item['value'];
            }
        }

        return false;
    }

    public function getIgnoredFields(): ?array
    {
        return $this->ignoredFields;
    }

    public function setIgnoredFields(array $ignoredFields): self
    {
        $this->ignoredFields = $ignoredFields;

        return $this;
    }

    public function appendIgnoredFields(array $ignoredFields): self
    {
        $this->ignoredFields = array_merge($this->ignoredFields, $ignoredFields);

        return $this;
    }
}
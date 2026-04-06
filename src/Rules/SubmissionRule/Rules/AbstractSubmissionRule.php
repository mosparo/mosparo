<?php

namespace Mosparo\Rules\SubmissionRule\Rules;

use Mosparo\Rules\SubmissionRule\SubmissionRuleInterface;

abstract class AbstractSubmissionRule implements SubmissionRuleInterface
{
    protected string $key = '';
    protected string $name = '';
    protected string $summary = '';
    protected string $description = '';
    protected float $defaultRating = 5;

    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDefaultRating(): float
    {
        return $this->defaultRating;
    }
}
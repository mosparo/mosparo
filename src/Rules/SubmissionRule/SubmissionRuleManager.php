<?php

namespace Mosparo\Rules\SubmissionRule;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;

class SubmissionRuleManager
{
    protected array $rules = [];

    public function __construct(RewindableGenerator $generator)
    {
        foreach ($generator as $rule) {
            $this->rules[] = $rule;
        }
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function getRule($key): ?SubmissionRuleInterface
    {
        foreach ($this->rules as $rule) {
            if ($rule->getKey() === $key) {
                return $rule;
            }
        }

        return null;
    }

    public function getRuleKeys(): array
    {
        $key = [];
        foreach ($this->rules as $rule) {
            $key[] = $rule->getKey();
        }

        return $key;
    }
}
<?php

namespace Mosparo\Rule;

use Mosparo\Rule\Type\RuleTypeInterface;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;

class RuleTypeManager
{
    protected array $ruleTypes = [];

    public function __construct(RewindableGenerator $generator)
    {
        foreach ($generator as $ruleType) {
            $this->ruleTypes[] = $ruleType;
        }
    }

    public function getRuleTypes(): array
    {
        return $this->ruleTypes;
    }

    public function getRuleType($key): ?RuleTypeInterface
    {
        foreach ($this->ruleTypes as $ruleType) {
            if ($ruleType->getKey() === $key) {
                return $ruleType;
            }
        }

        return null;
    }

    public function getRuleTypeKeys(): array
    {
        $key = [];
        foreach ($this->ruleTypes as $ruleType) {
            $key[] = $ruleType->getKey();
        }

        return $key;
    }
}
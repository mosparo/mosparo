<?php

namespace Mosparo\Rule\Type;

abstract class AbstractRuleType implements RuleTypeInterface
{
    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getSubtypes(): array
    {
        return $this->subtypes;
    }

    public function getFormClass(): string
    {
        return $this->formClass;
    }

    public function getTesterClass(): string
    {
        return $this->testerClass;
    }

    public function getTargetFieldKeys(): array
    {
        return $this->targetFieldKeys;
    }

    public function getHelpTemplate(): string
    {
        return $this->helpTemplate;
    }
}
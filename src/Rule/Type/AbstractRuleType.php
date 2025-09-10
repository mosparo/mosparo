<?php

namespace Mosparo\Rule\Type;

abstract class AbstractRuleType implements RuleTypeInterface
{
    protected string $key = '';
    protected string $name = '';
    protected string $description = '';
    protected string $icon = '';
    protected array $subtypes = [];
    protected string $formClass = '';
    protected string $testerClass = '';
    protected array $targetFieldKeys = [];
    protected bool $allowAddMultiple = true;

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

    public function allowAddMultiple(): bool
    {
        return ($this->allowAddMultiple);
    }

    public function formatValue(string $value, string $locale = ''): string
    {
        return $value;
    }

    public function getValidatorPattern(): array
    {
        return [];
    }
}
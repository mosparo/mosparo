<?php

namespace Mosparo\Rule\Type;

interface RuleTypeInterface
{
    public function getKey(): string;
    public function getName(): string;
    public function getDescription(): string;
    public function getIcon(): string;
    public function getSubtypes(): array;
    public function getFormClass(): string;
    public function getTesterClass(): string;
    public function getTargetFieldKeys(): array;
    public function getHelpTemplate(): string;
    public function allowAddMultiple(): bool;
    public function formatValue(string $value, string $locale = ''): string;
}
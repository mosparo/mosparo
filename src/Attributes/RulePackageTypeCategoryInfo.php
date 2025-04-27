<?php

namespace Mosparo\Attributes;

use Mosparo\Enum\RulePackageTypeCategory;
use ReflectionEnumUnitCase;

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
class RulePackageTypeCategoryInfo
{
    public readonly string $key;
    public readonly string $title;
    public readonly string $description;

    public function __construct(string $key, string $title, string $description)
    {
        $this->key = $key;
        $this->title = $title;
        $this->description = $description;
    }

    public static function from(RulePackageTypeCategory $type): self
    {
        $reflection = new ReflectionEnumUnitCase(RulePackageTypeCategory::class, $type->name);

        return $reflection->getAttributes(self::class)[0]->newInstance();
    }
}
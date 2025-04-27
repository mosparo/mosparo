<?php

namespace Mosparo\Attributes;

use Mosparo\Enum\RulePackageType;
use Mosparo\Enum\RulePackageTypeCategory;
use ReflectionEnumUnitCase;

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
class RulePackageTypeInfo
{
    public readonly string $key;
    public readonly RulePackageTypeCategory $category;
    public readonly string $title;
    public readonly string $description;
    public readonly string $icon;

    public function __construct(string $key, RulePackageTypeCategory $category, string $title, string $description, string $icon)
    {
        $this->key = $key;
        $this->category = $category;
        $this->title = $title;
        $this->description = $description;
        $this->icon = $icon;
    }

    public static function from(RulePackageType $type): self
    {
        $reflection = new ReflectionEnumUnitCase(RulePackageType::class, $type->name);

        return $reflection->getAttributes(self::class)[0]->newInstance();
    }
}
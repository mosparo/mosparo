<?php

namespace Mosparo\Attributes;

use Mosparo\Enum\AuthSource;
use ReflectionEnumUnitCase;

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
class AuthSourceInfo
{
    public readonly string $description;
    public readonly string $icon;

    public function __construct(string $description, string $icon)
    {
        $this->description = $description;
        $this->icon = $icon;
    }

    public static function from(AuthSource $key): self
    {
        $reflection = new ReflectionEnumUnitCase(AuthSource::class, $key->name);

        return $reflection->getAttributes(self::class)[0]->newInstance();
    }
}

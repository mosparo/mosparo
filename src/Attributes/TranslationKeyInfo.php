<?php

namespace Mosparo\Attributes;

use Mosparo\Enum\TranslationKey;
use ReflectionEnumUnitCase;

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
class TranslationKeyInfo
{
    public readonly string $frontendKey;

    public function __construct(string $frontendKey)
    {
        $this->frontendKey = $frontendKey;
    }

    public static function from(TranslationKey $key): self
    {
        $reflection = new ReflectionEnumUnitCase(TranslationKey::class, $key->name);

        return $reflection->getAttributes(self::class)[0]->newInstance();
    }
}
<?php

namespace Mosparo\Util;

class ChoicesUtil
{
    public static function buildChoices(array $subtypes): array
    {
        $choices = [];
        foreach ($subtypes as $subtype) {
            $choices[$subtype['name']] = $subtype['key'];
        }

        return $choices;
    }
}
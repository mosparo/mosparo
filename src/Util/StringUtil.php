<?php

namespace Mosparo\Util;

class StringUtil
{
    public static function obfuscateString(string $string): string
    {
        return substr($string, 0, 4) . str_repeat('*', strlen($string) - 8) . substr($string, -4);
    }
}
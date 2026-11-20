<?php

namespace Mosparo\Util;

class StringUtil
{
    public static function obfuscateString(string $string): string
    {
        if (strlen($string) <= 8) {
            return str_repeat('*', strlen($string));
        }

        return substr($string, 0, 4) . str_repeat('*', strlen($string) - 8) . substr($string, -4);
    }

    public static function toHex(string $string): string
    {
        return bin2hex($string);
    }
}

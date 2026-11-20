<?php

namespace Mosparo\Util;

class HeaderUtil
{
    public static function convertHeaderName(string $name): string
    {
        if (!$name) {
            return '';
        }

        $prefix = (str_starts_with($name, 'HTTP_')) ? '' : 'HTTP_';

        return $prefix . strtoupper(str_replace('-', '_', $name));
    }
}
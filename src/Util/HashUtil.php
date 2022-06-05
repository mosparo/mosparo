<?php

namespace Mosparo\Util;

class HashUtil
{
    public static function hash($value)
    {
        return hash('whirlpool', $value);
    }

    public static function sha256Hash($value)
    {
        return hash('sha256', $value);
    }
}
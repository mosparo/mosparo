<?php

namespace Mosparo\Util;

class HashUtil
{
    public static function hash($value)
    {
        return hash('whirlpool', $value);
    }
}
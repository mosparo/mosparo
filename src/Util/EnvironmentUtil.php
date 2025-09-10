<?php

namespace Mosparo\Util;

class EnvironmentUtil
{
    public static function getMemoryLimitInBytes(): int
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit == -1) {
            $memoryLimit = '256M';
        }

        sscanf($memoryLimit, '%u%c', $memory, $unit);
        if (isset($unit)) {
            $memory = $memory * pow(1024, strpos(' kmg', strtolower($unit)));
        }

        return $memory ?? 0;
    }
}
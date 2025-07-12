<?php

namespace Mosparo\Util;

class PathUtil
{
    public static function prepareFilePath(string $path, bool $toLowerCase = false): string
    {
        // The slash ('/') is the default directory separator in all our paths, we do not need to replace it.
        if (DIRECTORY_SEPARATOR === '/') {
            return $path;
        }

        // If we have a different directory separator, replace the slash in the path and return the path.
        $preparedPath = str_replace('/', DIRECTORY_SEPARATOR, $path);

        // Convert to lowercase to be able to compare on case-insensitive file systems.
        if ($toLowerCase) {
            return strtolower($preparedPath);
        }

        return $preparedPath;
    }
}
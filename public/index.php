<?php

use Mosparo\Kernel;

// If the request is a request for the project related css resource
// redirect here directly without letting Symfony decide to speed up the process
$mappingFile = __DIR__ . '/resources/mappings.php';
if (file_exists($mappingFile)) {
    $resourceFiles = include($mappingFile);
    $requestUri = $_SERVER['REQUEST_URI'];

    if (isset($resourceFiles[$requestUri])) {
        $targetUri = $resourceFiles[$requestUri];

        if (strpos($targetUri, '/resources/') === 0 && file_exists(__DIR__ . $resourceFiles[$requestUri])) {
            $cssFileDate = filemtime(__DIR__ . $resourceFiles[$requestUri]);
            if ($cssFileDate) {
                header('Last-Modified: ' . gmdate('D, d m Y H:i:s T', $cssFileDate));
            }
        }

        header('Cache-Control: no-cache, public');
        header('Location: ' . $resourceFiles[$requestUri], true, 307);
        exit;
    }
}

// Add a special check to show a better error message in case an user forgot to copy hidden files
if (!file_exists(__DIR__ . '/../.env')) {
    die('.env file not found! Have you forgot to copy it?');
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};

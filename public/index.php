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

        if ($_SERVER['HTTP_X_FORWARDED_PREFIX']) {
            $targetUri = '/' . trim($_SERVER['HTTP_X_FORWARDED_PREFIX'], '/') . $targetUri;
        }

        header('Cache-Control: no-cache, public');
        header('Location: ' . $targetUri, true, 307);
        exit;
    }
}

// Add a special check to show a better error message in case an user forgot to copy hidden files
if (!file_exists(__DIR__ . '/../.env')) {
    die('.env file not found! Have you forgot to copy it?');
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    // Replace the headers if there was configured a new name for headers in the security settings
    // in the administration interface. We do not replace the headers if the TRUSTED_PROXIES env
    // variable is set in the .env.local.
    $configFile = dirname(__DIR__) . '/config/env.mosparo.php';
    if (file_exists($configFile) && ($context['TRUSTED_PROXIES'] ?? '') === '%default_trusted_proxies%') {
        $envConfig = include($configFile);

        $replaceForwardedForHeader = $envConfig['replace_forwarded_for_header'] ?? '';
        if ($replaceForwardedForHeader && isset($_SERVER[$replaceForwardedForHeader])) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $_SERVER[$replaceForwardedForHeader];
        }

        $replaceForwardedProtoHeader = $envConfig['replaced_forwarded_proto_header'] ?? '';
        if ($replaceForwardedProtoHeader && isset($_SERVER[$replaceForwardedProtoHeader])) {
            $_SERVER['HTTP_X_FORWARDED_PROTO'] = $_SERVER[$replaceForwardedProtoHeader];
        }
    }

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};

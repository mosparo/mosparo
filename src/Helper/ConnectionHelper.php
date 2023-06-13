<?php

namespace Mosparo\Helper;

class ConnectionHelper
{
    /**
     * Returns an array with the information about the native internet connection and the curl extension.
     *
     * @return array
     */
    public function checkIfDownloadIsPossible(): array
    {
        $data = [
            'allowUrlFopen' => false,
            'downloadPossible' => false,
            'curlExtension' => false,
            'curlAvailable' => false,
        ];

        $curlVersion = phpversion('curl');
        if ($curlVersion) {
            $data['curlExtension'] = true;

            $data['curlAvailable'] = false;
            if (function_exists('curl_exec') && function_exists('curl_multi_exec')) {
                $data['curlAvailable'] = true;
            }
        }

        $allowUrlFopen = ini_get('allow_url_fopen');
        if ($allowUrlFopen) {
            $data['allowUrlFopen'] = true;
        }

        if ($data['allowUrlFopen'] || $data['curlAvailable']) {
            $data['downloadPossible'] = true;
        }

        return $data;
    }

    /**
     * Returns true if the download from the internet is possible. It does not evaluate if the connection and DNS
     * resolution is working.
     *
     * @return bool
     */
    public function isDownloadPossible(): bool
    {
        $downloadCheck = $this->checkIfDownloadIsPossible();

        return $downloadCheck['downloadPossible'];
    }

    /**
     * Returns true if curl is unavailable or the curl_exec or curl_multi_exec methods are disabled, and the native
     * client should be used.
     *
     * @return bool
     */
    public function useNativeConnection(): bool
    {
        $downloadCheck = $this->checkIfDownloadIsPossible();

        return (!$downloadCheck['curlExtension'] || !$downloadCheck['curlAvailable']);
    }
}
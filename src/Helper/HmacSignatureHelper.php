<?php

namespace Mosparo\Helper;

class HmacSignatureHelper
{
    public function createSignatureFromArray(array $data, $privateKey): string
    {
        return $this->createSignature($this->prepareData($data), $privateKey);
    }

    public function prepareData(array $data): string
    {
        $data = array_change_key_case($data, CASE_LOWER);
        ksort($data);

        return http_build_query($data);
    }

    public function createSignature(string $payload, $privateKey): string
    {
        return hash_hmac('sha256', $payload, $privateKey);
    }
}
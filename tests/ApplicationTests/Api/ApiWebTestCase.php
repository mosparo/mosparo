<?php

namespace Mosparo\Tests\ApplicationTests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiWebTestCase extends WebTestCase
{
    protected function generateAuthorizationHeader(string $apiEndpoint, array $data, string $publicKey, string $privateKey)
    {
        $jsonData = str_replace('[]', '{}', json_encode($data));
        $hmac = hash_hmac('sha256', $apiEndpoint . $jsonData, $privateKey);
        return base64_encode($publicKey . ':' . $hmac);
    }
}
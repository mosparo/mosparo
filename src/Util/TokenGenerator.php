<?php

namespace Mosparo\Util;

/**
 * Class TokenGenerator
 * Source: https://github.com/FriendsOfSymfony/FOSUserBundle/blob/b6246f838e6ef2aa9b67ae4a5690a7978a209eff/Util/TokenGenerator.php
 *
 * @package Mosparo\Util
 */
class TokenGenerator
{
    public function generateToken()
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
<?php

namespace Mosparo\Util;

use IPLib\Factory;
use IPLib\Range\Subnet;

class IpUtil
{
    public static function isIpAllowed($ipAddress, $ipAllowList): bool
    {
        $items = self::convertToArray($ipAllowList);
        if (empty($items)) {
            return true;
        }

        foreach ($items as $item) {
            if (strpos($item, '/') !== false && self::isIpInSubnet($item, $ipAddress)) {
                return true;
            } else if ($item === $ipAddress) {
                return true;
            }
        }

        return false;
    }

    public static function isIpInSubnet(string $subnetAddress, string $ipAddress): bool
    {
        $address = Factory::parseAddressString($ipAddress);
        $subnet = Subnet::parseString($subnetAddress);

        if ($address !== null &&
            $subnet !== null &&
            $address->getAddressType() == $subnet->getAddressType() &&
            $subnet->contains($address)
        ) {
            return true;
        }

        return false;
    }

    public static function convertToArray(?string $allowList): array
    {
        if (!$allowList) {
            return [];
        }

        return preg_split('/\r\n|\r|\n|,/', $allowList);
    }

    public static function isValid(?string $ipAddress): bool
    {
        if (!trim($ipAddress)) {
            return false;
        }

        if (strpos($ipAddress, '/') !== false) {
            return (Subnet::parseString($ipAddress) !== null);
        } else {
            return (Factory::parseAddressString($ipAddress) !== null);
        }
    }
}
<?php

namespace Mosparo\Util;

use IPLib\Address\IPv6;
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

    public static function convertToArray($allowList): array
    {
        if (!$allowList) {
            return [];
        }

        if (is_array($allowList)) {
            return $allowList;
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

    public static function getAddressForRateLimiting(string $ipAddress): string
    {
        $address = Factory::parseAddressString($ipAddress);
        if ($address === null) {
            return $ipAddress;
        }

        if ($address instanceof IPv6) {
            // IPv4-mapped (::ffff:1.2.3.4) and 6to4 addresses are handled as IPv4
            $ipv4 = $address->toIPv4();
            if ($ipv4 !== null) {
                return $ipv4->toString();
            }

            return Factory::parseRangeString($address->toString() . '/64')->getStartAddress()->toString();
        }

        return $address->toString();
    }
}
<?php

namespace Mosparo\Rule\Tester;

use IPLib\Address\IPv4;
use IPLib\Range\Subnet;
use Mosparo\Rule\RuleEntityInterface;

class IpAddressRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, RuleEntityInterface $rule): array
    {
        $matchingItems = [];
        foreach ($rule->getItems() as $item) {
            $result = false;
            if ($item->getType() === 'ipAddress') {
                $result = $this->validateIpAddress($value, $item->getValue());
            } else if ($item->getType() === 'subnet') {
                $result = $this->validateSubnet($value, $item->getValue());
            }

            if ($result !== false) {
                $matchingItems[] = [
                    'type' => $item->getType(),
                    'value' => $item->getValue(),
                    'rating' => $this->calculateSpamRating($rule, $item),
                    'uuid' => $rule->getUuid()
                ];
            }
        }

        return $matchingItems;
    }

    protected function validateIpAddress($value, $itemValue): bool
    {
        $value = strtolower($value);
        $itemValue = strtolower($itemValue);

        if ($value === $itemValue) {
            return true;
        }

        return false;
    }

    protected function validateSubnet($value, $itemValue): bool
    {
        $address = IPv4::parseString($value);
        $subnet = Subnet::parseString($itemValue);

        if ($address->getAddressType() == $subnet->getAddressType() && $subnet->contains($address)) {
            return true;
        }

        return false;
    }
}
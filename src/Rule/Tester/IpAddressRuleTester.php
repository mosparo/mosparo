<?php

namespace Mosparo\Rule\Tester;

use IPLib\Factory;
use Mosparo\Rule\RuleEntityInterface;

class IpAddressRuleTester extends AbstractRuleTester
{
    public function validateData($key, $value, RuleEntityInterface $rule): array
    {
        $matchingItems = [];
        foreach ($rule->getItems() as $item) {
            $result = false;
            if ($item['type'] === 'ipAddress') {
                $result = $this->validateIpAddress($value, $item['value']);
            } else if ($item['type'] === 'subnet') {
                $result = $this->validateSubnet($value, $item['value']);
            }

            if ($result !== false) {
                $matchingItems[] = [
                    'type' => $item['type'],
                    'value' => $item['value'],
                    'rating' => $this->calculateSpamRating($rule, $item),
                    'uuid' => $rule->getUuid()
                ];
            }
        }

        return $matchingItems;
    }

    protected function validateIpAddress($value, $itemValue)
    {
        if ($value === $itemValue) {
            return true;
        }

        return false;
    }

    protected function validateSubnet($value, $itemValue)
    {
        $address = Factory::addressFromString($value);
        $subnet = Factory::rangeFromString($itemValue);

        if ($address->getAddressType() == $subnet->getAddressType() && $subnet->contains($address)) {
            return true;
        }

        return false;
    }
}
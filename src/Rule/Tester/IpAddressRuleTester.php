<?php

namespace Mosparo\Rule\Tester;

use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use IPLib\Factory;
use IPLib\Range\Subnet;
use Mosparo\Rule\RuleItemEntityInterface;
use Mosparo\Util\HashUtil;

class IpAddressRuleTester extends AbstractRuleTester
{
    public function buildExpressions(QueryBuilder $qb, Orx $orExpr, array $fieldData, ?string $value)
    {
        $orExpr->add($qb->expr()->andX()
            ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('ipAddress')))
            ->add($qb->expr()->eq('i.hashedValue', $qb->createNamedParameter(HashUtil::hashFast($value))))
        );

        $orExpr->add($qb->expr()->andX()
            ->add($qb->expr()->eq('i.type', $qb->createNamedParameter('subnet')))
            ->add($qb->expr()->like($qb->createNamedParameter($value), 'i.preparedValue'))
        );
    }

    public function validateData(string $key, mixed $lowercaseValue, mixed $originalValue, RuleItemEntityInterface $item): array
    {
        $matchingItems = [];
        $result = false;
        if ($item->getType() === 'ipAddress') {
            $result = $this->validateIpAddress($lowercaseValue, $item->getValue());
        } else if ($item->getType() === 'subnet') {
            $result = $this->validateSubnet($lowercaseValue, $item->getValue());
        }

        if ($result !== false) {
            $matchingItems = [
                'type' => $item->getType(),
                'value' => $item->getValue(),
                'rating' => $this->calculateSpamRating($item),
                'uuid' => $item->getParent()->getUuid(),
            ];
        }

        return $matchingItems;
    }

    protected function validateIpAddress($value, $itemValue): bool
    {
        $itemValue = strtolower($itemValue);

        if ($value === $itemValue) {
            return true;
        }

        return false;
    }

    protected function validateSubnet($value, $itemValue): bool
    {
        $address = Factory::parseAddressString($value);
        $subnet = Subnet::parseString($itemValue);

        if ($address->getAddressType() == $subnet->getAddressType() && $subnet->contains($address)) {
            return true;
        }

        return false;
    }
}
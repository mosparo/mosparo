<?php

namespace Mosparo\Rule;

use IPLib\Address\IPv6;
use IPLib\Factory;
use Doctrine\ORM\Mapping as ORM;
use Mosparo\Util\HashUtil;

trait PreparedRuleItemTrait
{
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $preparedValue = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    protected ?string $hashedValue = null;

    public function setPreparedValue(?string $preparedValue): self
    {
        $this->preparedValue = $preparedValue;

        return $this;
    }

    public function getPreparedValue(): ?string
    {
        return $this->preparedValue;
    }

    public function setHashedValue(?string $hashedValue): self
    {
        $this->hashedValue = $hashedValue;

        return $this;
    }

    public function getHashedValue(): ?string
    {
        return $this->hashedValue;
    }

    #[ORM\PreFlush]
    public function preFlush(): void
    {
        $this->setPreparedValue($this->prepareValue($this->getType(), strtolower($this->getValue())));
        $this->setHashedValue(HashUtil::hashFast(strtolower($this->getValue())));
    }

    protected function prepareValue(string $type, string $value): string
    {
        $wildcardTypes = ['text', 'uaText'];
        if (in_array($type, $wildcardTypes)) {
            $value = str_replace('*', '%', $value);
        }

        $inTextTypes = ['text', 'wExact', 'wFull', 'uaText', 'email', 'url', 'domain'];
        if (in_array($type, $inTextTypes)) {
            $value = '%' . trim($value, '%') . '%';
        }

        $regexTypes = ['regex', 'uaRegex'];
        if (in_array($type, $regexTypes)) {
            $value = '%';
        }

        if ($type === 'subnet') {
            $range = Factory::parseRangeString($value);

            // The range is invalid, so we cannot continue.
            if (!$range) {
                return $value;
            }

            if (!$range->asPattern()) {
                $prefix = $range->getNetworkPrefix();

                if ($range->getStartAddress() instanceof IPv6) {
                    $prefixes = [4, 8, 12, 16, 20, 24, 28, 32, 36, 40, 44, 48, 52, 56, 60, 64];
                } else {
                    $prefixes = [8, 16, 24];
                }

                $nearestPrefix = $this->findNextLowerPrefix($prefix, $prefixes);
                $range = Factory::parseRangeString($range->getStartAddress()->toString() . '/' . $nearestPrefix);
            }

            if ($range->getStartAddress() instanceof IPv6) {
                $value = str_replace(':*', '', $range->asPattern()->toString()) . ':%';
            } else {
                $value = str_replace('.*', '', $range->asPattern()->toString()) . '.%';
            }
        }

        return $value;
    }

    protected function findNextLowerPrefix($searchedPrefix, $prefixes): int
    {
        $closestPrefix = 0;

        foreach ($prefixes as $prefix) {
            if ($prefix < $searchedPrefix && $prefix > $closestPrefix) {
                $closestPrefix = $prefix;
            }
        }

        return $closestPrefix;
    }
}
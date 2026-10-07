<?php

namespace Mosparo\Rules\FieldRule;

use Doctrine\ORM\Mapping as ORM;
use IPLib\Address\IPv6;
use IPLib\Range\Subnet;
use Mosparo\Util\HashUtil;
use Mosparo\Util\UrlUtil;

trait PreparedRuleItemTrait
{
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $preparedValue = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    protected ?string $hashedValue = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    protected ?int $preparationVersion = null;

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

    public function getPreparationVersion(): ?int
    {
        return $this->preparationVersion;
    }

    #[ORM\PreFlush]
    public function preFlush(): void
    {
        $this->setPreparedValue($this->prepareValue($this->getType(), mb_strtolower($this->getValue())));
        $this->setHashedValue($this->prepareHash($this->getType(), mb_strtolower($this->getValue())));

        $this->preparationVersion = RuleItemEntityInterface::PREPARATION_VERSION;
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
            $subnet = Subnet::parseString($value);

            // The subnet is invalid, so the item can never match.
            if (!$subnet) {
                return '';
            }

            // Compare the expanded form (e.g. 2001:0db8:0000:..., 010.001.002.003) and cut it after the last
            // complete hex digit (IPv6) or octet (IPv4) of the prefix; validateSubnet() does the exact check.
            $startAddress = $subnet->getStartAddress();
            $prefix = $subnet->getNetworkPrefix();
            if ($startAddress instanceof IPv6) {
                $digits = intdiv($prefix, 4);
                $length = $digits + intdiv($digits, 4);
            } else {
                $length = intdiv($prefix, 8) * 4;
            }

            $value = substr($startAddress->toString(true), 0, $length) . '%';
        }

        return $value;
    }

    protected function prepareHash(string $type, string $value): string
    {
        return match($type) {
            'domain' => HashUtil::hashFast(trim($value, './')),
            'email' => HashUtil::hashFast(trim($value)),
            'url' => HashUtil::hashFast(UrlUtil::normalizeUrl($value)),
            default => HashUtil::hashFast($value)
        };
    }
}
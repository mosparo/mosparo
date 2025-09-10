<?php

namespace Mosparo\Twig;

use Mosparo\Attributes\RulePackageTypeInfo;
use Mosparo\Enum\RulePackageType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RulePackageExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('rule_package_type', [$this, 'getRulePackageType']),
            new TwigFunction('get_rule_package_type_info', [$this, 'getRulePackageTypeInfo']),
        ];
    }

    public function getRulePackageType(int $type): ?RulePackageType
    {
        return RulePackageType::from($type);
    }

    public function getRulePackageTypeInfo(RulePackageType $type): ?RulePackageTypeInfo
    {
        return RulePackageTypeInfo::from($type);
    }
}
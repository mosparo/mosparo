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
            new TwigFunction('get_rule_package_type_info', [$this, 'getInfo']),
        ];
    }

    public function getInfo(RulePackageType $type): ?RulePackageTypeInfo
    {
        return RulePackageTypeInfo::from($type);
    }
}
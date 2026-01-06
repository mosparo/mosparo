<?php

namespace Mosparo\Twig;

use Mosparo\Attributes\RulePackageTypeInfo;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Enum\RulePackageType;
use Mosparo\Helper\RulePackageHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RulePackageExtension extends AbstractExtension
{
    protected RulePackageHelper $rulePackageHelper;

    public function __construct(RulePackageHelper $rulePackageHelper)
    {
        $this->rulePackageHelper = $rulePackageHelper;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('rule_package_type', [$this, 'getRulePackageType']),
            new TwigFunction('get_rule_package_type_info', [$this, 'getRulePackageTypeInfo']),
            new TwigFunction('rule_package_count_rule_items', [$this, 'countRuleItems']),
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

    public function countRuleItems(RulePackageRuleCache $rprc): int
    {
        if ($rprc->getNumberOfItems()) {
            return $rprc->getNumberOfItems();
        }

        return $this->rulePackageHelper->countRuleItemsForRule($rprc);
    }
}
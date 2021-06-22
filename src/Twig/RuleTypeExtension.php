<?php

namespace Mosparo\Twig;

use Mosparo\Rule\RuleTypeManager;
use Mosparo\Rule\Type\RuleTypeInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RuleTypeExtension extends AbstractExtension
{
    protected $ruleTypeManager;

    public function __construct(RuleTypeManager $ruleTypeManager)
    {
        $this->ruleTypeManager = $ruleTypeManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('rule_type', [$this, 'getRuleType'])
        ];
    }

    public function getRuleType($key): ?RuleTypeInterface
    {
        return $this->ruleTypeManager->getRuleType($key);
    }
}
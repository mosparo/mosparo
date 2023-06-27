<?php

namespace Mosparo\Twig;

use Mosparo\Repository\RuleRepository;
use Mosparo\Repository\RulesetRuleCacheRepository;
use Mosparo\Rule\RuleEntityInterface;
use Mosparo\Rule\RuleTypeManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RuleExtension extends AbstractExtension
{
    protected UrlGeneratorInterface $router;

    protected RuleRepository $ruleRepository;

    protected RulesetRuleCacheRepository $rulesetRuleCacheRepository;

    protected RuleTypeManager $ruleTypeManager;

    public function __construct(UrlGeneratorInterface $router, RuleRepository $ruleRepository, RulesetRuleCacheRepository $rulesetRuleCacheRepository, RuleTypeManager $ruleTypeManager)
    {
        $this->router = $router;
        $this->ruleRepository = $ruleRepository;
        $this->rulesetRuleCacheRepository = $rulesetRuleCacheRepository;
        $this->ruleTypeManager = $ruleTypeManager;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('format_rule_value', [$this, 'formatRuleValue']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_rule', [$this, 'getRule']),
            new TwigFunction('get_rule_detail_url', [$this, 'getRuleDetailUrl']),
        ];
    }

    public function getRule($uuid): ?RuleEntityInterface
    {
        $rule = $this->ruleRepository->findOneBy(['uuid' => $uuid]);
        if ($rule) {
            return $rule;
        }

        $rulesetRuleCache = $this->rulesetRuleCacheRepository->findOneBy(['uuid' => $uuid]);
        if ($rulesetRuleCache) {
            return $rulesetRuleCache;
        }

        return null;
    }

    public function getRuleDetailUrl($uuid): ?string
    {
        $rule = $this->ruleRepository->findOneBy(['uuid' => $uuid]);
        if ($rule) {
            return $this->router->generate('rule_edit', ['id' => $rule->getId()]);
        }

        $rulesetRuleCache = $this->rulesetRuleCacheRepository->findOneBy(['uuid' => $uuid]);
        if ($rulesetRuleCache) {
            return $this->router->generate('ruleset_view_rule', ['id' => $rulesetRuleCache->getRulesetCache()->getRuleset()->getId(), 'ruleUuid' => $rulesetRuleCache->getUuid()]);
        }

        return null;
    }

    public function formatRuleValue($value, $uuid, $locale = ''): string
    {
        $rule = $this->ruleRepository->findOneBy(['uuid' => $uuid]);
        if ($rule) {
            $ruleType = $this->ruleTypeManager->getRuleType($rule->getType());
            if ($ruleType) {
                return $ruleType->formatValue($value, $locale);
            }
        }

        $rulesetRuleCache = $this->rulesetRuleCacheRepository->findOneBy(['uuid' => $uuid]);
        if ($rulesetRuleCache) {
            $ruleType = $this->ruleTypeManager->getRuleType($rulesetRuleCache->getType());
            if ($ruleType) {
                return $ruleType->formatValue($value, $locale);
            }
        }

        return $value;
    }
}
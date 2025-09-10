<?php

namespace Mosparo\Twig;

use Mosparo\Repository\RuleRepository;
use Mosparo\Repository\RulePackageRuleCacheRepository;
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

    protected RulePackageRuleCacheRepository $rulePackageRuleCacheRepository;

    protected RuleTypeManager $ruleTypeManager;

    public function __construct(UrlGeneratorInterface $router, RuleRepository $ruleRepository, RulePackageRuleCacheRepository $rulePackageRuleCacheRepository, RuleTypeManager $ruleTypeManager)
    {
        $this->router = $router;
        $this->ruleRepository = $ruleRepository;
        $this->rulePackageRuleCacheRepository = $rulePackageRuleCacheRepository;
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

        $rulePackageRuleCache = $this->rulePackageRuleCacheRepository->findOneBy(['uuid' => $uuid]);
        if ($rulePackageRuleCache) {
            return $rulePackageRuleCache;
        }

        return null;
    }

    public function getRuleDetailUrl($uuid): ?string
    {
        $rule = $this->ruleRepository->findOneBy(['uuid' => $uuid]);
        if ($rule) {
            return $this->router->generate('rule_edit', ['_projectId' => $rule->getProject()->getId(), 'id' => $rule->getId()]);
        }

        $rulePackageRuleCache = $this->rulePackageRuleCacheRepository->findOneBy(['uuid' => $uuid]);
        if ($rulePackageRuleCache) {
            return $this->router->generate('rule_package_view_rule', [
                '_projectId' => $rulePackageRuleCache->getProject()->getId(),
                'id' => $rulePackageRuleCache->getRulePackageCache()->getRulePackage()->getId(),
                'ruleUuid' => $rulePackageRuleCache->getUuid()
            ]);
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

        $rulePackageRuleCache = $this->rulePackageRuleCacheRepository->findOneBy(['uuid' => $uuid]);
        if ($rulePackageRuleCache) {
            $ruleType = $this->ruleTypeManager->getRuleType($rulePackageRuleCache->getType());
            if ($ruleType) {
                return $ruleType->formatValue($value, $locale);
            }
        }

        return $value;
    }
}
<?php

namespace Mosparo\Twig;

use Mosparo\Repository\RuleRepository;
use Mosparo\Repository\RulesetRuleCacheRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RuleExtension extends AbstractExtension
{
    protected UrlGeneratorInterface $router;

    protected RuleRepository $ruleRepository;

    protected RulesetRuleCacheRepository $rulesetRuleCacheRepository;

    public function __construct(UrlGeneratorInterface $router, RuleRepository $ruleRepository, RulesetRuleCacheRepository $rulesetRuleCacheRepository)
    {
        $this->router = $router;
        $this->ruleRepository = $ruleRepository;
        $this->rulesetRuleCacheRepository = $rulesetRuleCacheRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_rule_detail_url', [$this, 'getRuleDetailUrl'])
        ];
    }

    public function getRuleDetailUrl($uuid): ?string
    {
        $rule = $this->ruleRepository->findOneBy(['uuid' => $uuid]);
        if ($rule !== null) {
            return $this->router->generate('rule_edit', ['id' => $rule->getId()]);
        }

        $rulesetRuleCache = $this->rulesetRuleCacheRepository->findOneBy(['uuid' => $uuid]);
        if ($rulesetRuleCache !== null) {
            return $this->router->generate('ruleset_view', ['id' => $rulesetRuleCache->getRulesetCache()->getRuleset()->getId()]);
        }

        return null;
    }
}
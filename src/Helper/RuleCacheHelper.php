<?php

namespace Mosparo\Helper;

use Mosparo\Rule\Cache\CachedRule;
use Mosparo\Rule\Cache\CachedRuleItem;
use Mosparo\Rule\RuleEntityInterface;
use Symfony\Contracts\Cache\CacheInterface;

class RuleCacheHelper
{
    protected CacheInterface $cache;

    protected ProjectHelper $projectHelper;

    protected bool $prepareRulesInSharedCache;

    protected int $rulesCacheTtl;

    public function __construct(CacheInterface $cache, ProjectHelper $projectHelper, bool $prepareRulesInSharedCache, int $rulesCacheTtl)
    {
        $this->cache = $cache;
        $this->projectHelper = $projectHelper;
        $this->prepareRulesInSharedCache = $prepareRulesInSharedCache;
        $this->rulesCacheTtl = $rulesCacheTtl;
    }

    public function loadRulesFromCache(): array
    {
        if (!$this->prepareRulesInSharedCache) {
            return [];
        }

        $baseKey = $this->getBaseKey();
        $ruleKeysCacheItem = $this->cache->getItem($baseKey);
        if (!$ruleKeysCacheItem->get()) {
            return [];
        }

        $rules = null;
        foreach ($ruleKeysCacheItem->get() as $key) {
            $cachedRuleCacheItem = $this->cache->getItem($key);

            if ($cachedRuleCacheItem->get()) {
                $rules[] = $cachedRuleCacheItem->get();
            }
        }

        return $rules;
    }

    public function storeRulesInCache(array $rules): void
    {
        if (!$this->prepareRulesInSharedCache) {
            return;
        }

        $baseKey = $this->getBaseKey();
        $ruleKeys = [];
        foreach ($rules as $rule) {
            $ruleKey = $baseKey . 'r' . $rule->getId();
            $ruleKeys[] = $ruleKey;

            $cachedRule = $this->convertRuleToCachedRule($rule);

            $cachedRuleCacheItem = $this->cache->getItem($ruleKey);
            $cachedRuleCacheItem
                ->set($cachedRule)
                ->expiresAfter($this->rulesCacheTtl + 10); // Add 10 seconds so that the keys item expires first
            $this->cache->save($cachedRuleCacheItem);
        }

        $ruleKeysCacheItem = $this->cache->getItem($baseKey);
        $ruleKeysCacheItem
            ->set($ruleKeys)
            ->expiresAfter($this->rulesCacheTtl);
        $this->cache->save($ruleKeysCacheItem);
    }

    public function clearRulesCache(): void
    {
        if (!$this->prepareRulesInSharedCache) {
            return;
        }

        $baseKey = $this->getBaseKey();
        $ruleKeysCacheItem = $this->cache->getItem($baseKey);
        if (!$ruleKeysCacheItem->get()) {
            return;
        }

        try {
            foreach ($ruleKeysCacheItem->get() as $key) {
                $this->cache->delete($key);
            }

            $this->cache->delete($baseKey);
        } catch (\Exception $e) {
            // Catch all exceptions and do nothing
        }
    }

    protected function convertRuleToCachedRule(RuleEntityInterface $rule): RuleEntityInterface
    {
        if ($rule instanceof CachedRule) {
            return $rule;
        }

        $cachedRule = (new CachedRule())
            ->setUuid($rule->getUuid())
            ->setName($rule->getName())
            ->setDescription($rule->getDescription())
            ->setType($rule->getType())
            ->setSpamRatingFactor($rule->getSpamRatingFactor());

        foreach ($rule->getItems() as $item) {
            $cachedItem = (new CachedRuleItem())
                ->setUuid($item->getUuid())
                ->setType($item->getType())
                ->setValue($item->getValue())
                ->setSpamRatingFactor($item->getSpamRatingFactor());

            $cachedRule->getItems()->add($cachedItem);
        }

        return $cachedRule;
    }

    protected function getBaseKey(): string
    {
        if (!$this->projectHelper->hasActiveProject()) {
            return 'unknown' . uniqid();
        }

        return 'p'. $this->projectHelper->getActiveProject()->getId() . 'rc';
    }
}
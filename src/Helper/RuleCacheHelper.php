<?php

namespace Mosparo\Helper;

use Mosparo\Util\HashUtil;
use Symfony\Contracts\Cache\CacheInterface;

class RuleCacheHelper
{
    protected CacheInterface $cache;

    protected ProjectHelper $projectHelper;

    protected bool $useSharedCacheForRuleItems;

    protected int $ruleItemsCacheTtl;

    public function __construct(CacheInterface $cache, ProjectHelper $projectHelper, bool $useSharedCacheForRuleItems, int $ruleItemsCacheTtl)
    {
        $this->cache = $cache;
        $this->projectHelper = $projectHelper;
        $this->useSharedCacheForRuleItems = $useSharedCacheForRuleItems;
        $this->ruleItemsCacheTtl = $ruleItemsCacheTtl;
    }

    public function storeRuleItemsForValue(mixed $value, array $processedItemIds): void
    {
        if (!$this->useSharedCacheForRuleItems) {
            return;
        }

        $key = $this->getBaseKey() . '_' . HashUtil::hashFast($value);

        $cachedItems = $this->cache->getItem($key);
        $cachedItems
            ->set($processedItemIds)
            ->expiresAfter($this->ruleItemsCacheTtl);
        $this->cache->save($cachedItems);
    }

    public function getRuleItemIdsForValue(mixed $value): ?array
    {
        if (!$this->useSharedCacheForRuleItems) {
            return null;
        }

        $key = $this->getBaseKey() . '_' . HashUtil::hashFast($value);
        $cachedItems = $this->cache->getItem($key);
        if (!$cachedItems->get()) {
            return null;
        }

        return $cachedItems->get();
    }

    protected function getBaseKey(): string
    {
        if (!$this->projectHelper->hasActiveProject()) {
            return 'unknown' . uniqid();
        }

        return 'p'. $this->projectHelper->getActiveProject()->getId() . 'rc';
    }
}
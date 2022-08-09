<?php

namespace Mosparo\Tests\UnitTests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mosparo\Entity\Project;
use Mosparo\Entity\Ruleset;
use Mosparo\Entity\RulesetCache;
use Mosparo\Entity\RulesetRuleCache;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class RulesetCacheTest extends TestCase
{
    const EXAMPLE_UUID = '00000000-0000-0000-0000-000000000000';

    public function testGetId()
    {
        $rulesetCache = new RulesetCache();

        $refObject = new ReflectionObject($rulesetCache);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulesetCache, '123');

        $this->assertEquals(123, $rulesetCache->getId());
    }

    public function testGetRuleset()
    {
        $ruleset = new Ruleset();

        $rulesetCache = new RulesetCache();
        $rulesetCache->setRuleset($ruleset);

        $this->assertSame($ruleset, $rulesetCache->getRuleset());
    }

    public function testGetRefreshedAt()
    {
        $dateTime = new \DateTime();

        $rulesetCache = new RulesetCache();
        $rulesetCache->setRefreshedAt($dateTime);

        $this->assertSame($dateTime, $rulesetCache->getRefreshedAt());
    }

    public function testGetUpdatedAt()
    {
        $dateTime = new \DateTime();

        $rulesetCache = new RulesetCache();
        $rulesetCache->setUpdatedAt($dateTime);

        $this->assertSame($dateTime, $rulesetCache->getUpdatedAt());
    }

    public function testGetRefreshInterval()
    {
        $rulesetCache = new RulesetCache();
        $rulesetCache->setRefreshInterval(86400);

        $this->assertEquals(86400, $rulesetCache->getRefreshInterval());
    }

    public function testGetItems()
    {
        $arrayCollection = new ArrayCollection([
            new RulesetRuleCache(),
            new RulesetRuleCache()
        ]);

        $rulesetCache = new RulesetCache();

        $refObject = new ReflectionObject($rulesetCache);
        $refProperty = $refObject->getProperty('rules');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulesetCache, $arrayCollection);

        $this->assertSame($arrayCollection, $rulesetCache->getRules());
    }

    public function testAddItem()
    {
        $arrayCollection = new ArrayCollection([
            new RulesetRuleCache(),
            new RulesetRuleCache(),
        ]);

        $rulesetCache = new RulesetCache();

        $refObject = new ReflectionObject($rulesetCache);
        $refProperty = $refObject->getProperty('rules');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulesetCache, $arrayCollection);

        $rulesetCache->addRule(new RulesetRuleCache);

        $this->assertCount(3, $rulesetCache->getRules());
        $this->assertSame($arrayCollection, $rulesetCache->getRules());
    }

    public function testRemoveItem()
    {
        $itemToRemove = new RulesetRuleCache();
        $arrayCollection = new ArrayCollection([
            new RulesetRuleCache(),
            $itemToRemove,
            new RulesetRuleCache(),
        ]);

        $rulesetCache = new RulesetCache();

        $itemToRemove->setRulesetCache($rulesetCache);

        $refObject = new ReflectionObject($rulesetCache);
        $refProperty = $refObject->getProperty('rules');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulesetCache, $arrayCollection);

        $rulesetCache->removeRule($itemToRemove);

        $this->assertCount(2, $rulesetCache->getRules());
        $this->assertSame($arrayCollection, $rulesetCache->getRules());
    }

    public function testFindItem()
    {
        $itemToFind = new RulesetRuleCache();
        $itemToFind->setUuid(self::EXAMPLE_UUID);

        $arrayCollection = new ArrayCollection([
            new RulesetRuleCache(),
            $itemToFind,
            new RulesetRuleCache(),
        ]);

        $rulesetCache = new RulesetCache();

        $refObject = new ReflectionObject($rulesetCache);
        $refProperty = $refObject->getProperty('rules');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulesetCache, $arrayCollection);

        $this->assertSame($itemToFind, $rulesetCache->findRule(self::EXAMPLE_UUID));
        $this->assertNull($rulesetCache->findRule('test123'));
    }

    public function testCannotFindItem()
    {
        $rulesetCache = new RulesetCache();

        $this->assertNull($rulesetCache->findRule('test123'));
    }

    public function testGetProject()
    {
        $project = new Project();

        $rulesetCache = new RulesetCache();
        $rulesetCache->setProject($project);

        $this->assertSame($project, $rulesetCache->getProject());
    }
}

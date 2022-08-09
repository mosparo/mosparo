<?php

namespace Mosparo\Tests\UnitTests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mosparo\Entity\Project;
use Mosparo\Entity\RulesetCache;
use Mosparo\Entity\RulesetRuleCache;
use Mosparo\Entity\RulesetRuleItemCache;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class RulesetRuleCacheTest extends TestCase
{
    const EXAMPLE_UUID = '00000000-0000-0000-0000-000000000000';

    public function testGetId()
    {
        $rulesetRuleCache = new RulesetRuleCache();

        $refObject = new ReflectionObject($rulesetRuleCache);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulesetRuleCache, '123');

        $this->assertEquals(123, $rulesetRuleCache->getId());
    }

    public function testGetUuid()
    {
        $rulesetRuleCache = new RulesetRuleCache();
        $rulesetRuleCache->setUuid(self::EXAMPLE_UUID);

        $this->assertEquals(self::EXAMPLE_UUID, $rulesetRuleCache->getUuid());
    }

    public function testGetRulesetCache()
    {
        $rulesetCache = new RulesetCache();

        $rulesetRuleCache = new RulesetRuleCache();
        $rulesetRuleCache->setRulesetCache($rulesetCache);

        $this->assertSame($rulesetCache, $rulesetRuleCache->getRulesetCache());
    }

    public function testGetName()
    {
        $rulesetRuleCache = new RulesetRuleCache();
        $rulesetRuleCache->setName('test-name');

        $this->assertEquals('test-name', $rulesetRuleCache->getName());
    }

    public function testGetDescription()
    {
        $rulesetRuleCache = new RulesetRuleCache();
        $rulesetRuleCache->setDescription('test-description');

        $this->assertEquals('test-description', $rulesetRuleCache->getDescription());
    }

    public function testGetType()
    {
        $rulesetRuleCache = new RulesetRuleCache();
        $rulesetRuleCache->setType('test-type');

        $this->assertEquals('test-type', $rulesetRuleCache->getType());
    }

    public function testGetItems()
    {
        $arrayCollection = new ArrayCollection([
            new RulesetRuleItemCache(),
            new RulesetRuleItemCache()
        ]);

        $rulesetRuleCache = new RulesetRuleCache();

        $refObject = new ReflectionObject($rulesetRuleCache);
        $refProperty = $refObject->getProperty('items');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulesetRuleCache, $arrayCollection);

        $this->assertSame($arrayCollection, $rulesetRuleCache->getItems());
    }

    public function testAddItem()
    {
        $arrayCollection = new ArrayCollection([
            new RulesetRuleItemCache(),
            new RulesetRuleItemCache(),
        ]);

        $rulesetRuleCache = new RulesetRuleCache();

        $refObject = new ReflectionObject($rulesetRuleCache);
        $refProperty = $refObject->getProperty('items');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulesetRuleCache, $arrayCollection);

        $rulesetRuleCache->addItem(new RulesetRuleItemCache);

        $this->assertCount(3, $rulesetRuleCache->getItems());
        $this->assertSame($arrayCollection, $rulesetRuleCache->getItems());
    }

    public function testRemoveItem()
    {
        $itemToRemove = new RulesetRuleItemCache();
        $arrayCollection = new ArrayCollection([
            new RulesetRuleItemCache(),
            $itemToRemove,
            new RulesetRuleItemCache(),
        ]);

        $rulesetRuleCache = new RulesetRuleCache();

        $itemToRemove->setRulesetRuleCache($rulesetRuleCache);

        $refObject = new ReflectionObject($rulesetRuleCache);
        $refProperty = $refObject->getProperty('items');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulesetRuleCache, $arrayCollection);

        $rulesetRuleCache->removeItem($itemToRemove);

        $this->assertCount(2, $rulesetRuleCache->getItems());
        $this->assertSame($arrayCollection, $rulesetRuleCache->getItems());
    }

    public function testFindItem()
    {
        $itemToFind = new RulesetRuleItemCache();
        $itemToFind->setUuid(self::EXAMPLE_UUID);

        $arrayCollection = new ArrayCollection([
            new RulesetRuleItemCache(),
            $itemToFind,
            new RulesetRuleItemCache(),
        ]);

        $rulesetRuleCache = new RulesetRuleCache();

        $refObject = new ReflectionObject($rulesetRuleCache);
        $refProperty = $refObject->getProperty('items');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulesetRuleCache, $arrayCollection);

        $this->assertSame($itemToFind, $rulesetRuleCache->findItem(self::EXAMPLE_UUID));
        $this->assertNull($rulesetRuleCache->findItem('test123'));
    }

    public function testCannotFindItem()
    {
        $rulesetRuleCache = new RulesetRuleCache();

        $this->assertNull($rulesetRuleCache->findItem('test123'));
    }

    public function testGetSpamRatingFactor()
    {
        $rulesetRuleCache = new RulesetRuleCache();
        $rulesetRuleCache->setSpamRatingFactor(5);

        $this->assertEquals(5, $rulesetRuleCache->getSpamRatingFactor());
    }

    public function testGetProject()
    {
        $project = new Project();

        $rulesetRuleCache = new RulesetRuleCache();
        $rulesetRuleCache->setProject($project);

        $this->assertSame($project, $rulesetRuleCache->getProject());
    }
}

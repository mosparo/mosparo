<?php

namespace Mosparo\Tests\UnitTests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mosparo\Entity\Project;
use Mosparo\Entity\RulePackageCache;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Entity\RulePackageRuleItemCache;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class RulePackageRuleCacheTest extends TestCase
{
    const EXAMPLE_UUID = '00000000-0000-0000-0000-000000000000';

    public function testGetId()
    {
        $rulePackageRuleCache = new RulePackageRuleCache();

        $refObject = new ReflectionObject($rulePackageRuleCache);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackageRuleCache, '123');

        $this->assertEquals(123, $rulePackageRuleCache->getId());
    }

    public function testGetUuid()
    {
        $rulePackageRuleCache = new RulePackageRuleCache();
        $rulePackageRuleCache->setUuid(self::EXAMPLE_UUID);

        $this->assertEquals(self::EXAMPLE_UUID, $rulePackageRuleCache->getUuid());
    }

    public function testGetRulePackageCache()
    {
        $rulePackageCache = new RulePackageCache();

        $rulePackageRuleCache = new RulePackageRuleCache();
        $rulePackageRuleCache->setRulePackageCache($rulePackageCache);

        $this->assertSame($rulePackageCache, $rulePackageRuleCache->getRulePackageCache());
    }

    public function testGetName()
    {
        $rulePackageRuleCache = new RulePackageRuleCache();
        $rulePackageRuleCache->setName('test-name');

        $this->assertEquals('test-name', $rulePackageRuleCache->getName());
    }

    public function testGetDescription()
    {
        $rulePackageRuleCache = new RulePackageRuleCache();
        $rulePackageRuleCache->setDescription('test-description');

        $this->assertEquals('test-description', $rulePackageRuleCache->getDescription());
    }

    public function testGetType()
    {
        $rulePackageRuleCache = new RulePackageRuleCache();
        $rulePackageRuleCache->setType('test-type');

        $this->assertEquals('test-type', $rulePackageRuleCache->getType());
    }

    public function testGetItems()
    {
        $arrayCollection = new ArrayCollection([
            new RulePackageRuleItemCache(),
            new RulePackageRuleItemCache()
        ]);

        $rulePackageRuleCache = new RulePackageRuleCache();

        $refObject = new ReflectionObject($rulePackageRuleCache);
        $refProperty = $refObject->getProperty('items');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackageRuleCache, $arrayCollection);

        $this->assertSame($arrayCollection, $rulePackageRuleCache->getItems());
    }

    public function testAddItem()
    {
        $arrayCollection = new ArrayCollection([
            new RulePackageRuleItemCache(),
            new RulePackageRuleItemCache(),
        ]);

        $rulePackageRuleCache = new RulePackageRuleCache();

        $refObject = new ReflectionObject($rulePackageRuleCache);
        $refProperty = $refObject->getProperty('items');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackageRuleCache, $arrayCollection);

        $rulePackageRuleCache->addItem(new RulePackageRuleItemCache);

        $this->assertCount(3, $rulePackageRuleCache->getItems());
        $this->assertSame($arrayCollection, $rulePackageRuleCache->getItems());
    }

    public function testRemoveItem()
    {
        $itemToRemove = new RulePackageRuleItemCache();
        $arrayCollection = new ArrayCollection([
            new RulePackageRuleItemCache(),
            $itemToRemove,
            new RulePackageRuleItemCache(),
        ]);

        $rulePackageRuleCache = new RulePackageRuleCache();

        $itemToRemove->setRulePackageRuleCache($rulePackageRuleCache);

        $refObject = new ReflectionObject($rulePackageRuleCache);
        $refProperty = $refObject->getProperty('items');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackageRuleCache, $arrayCollection);

        $rulePackageRuleCache->removeItem($itemToRemove);

        $this->assertCount(2, $rulePackageRuleCache->getItems());
        $this->assertSame($arrayCollection, $rulePackageRuleCache->getItems());
    }

    public function testFindItem()
    {
        $itemToFind = new RulePackageRuleItemCache();
        $itemToFind->setUuid(self::EXAMPLE_UUID);

        $arrayCollection = new ArrayCollection([
            new RulePackageRuleItemCache(),
            $itemToFind,
            new RulePackageRuleItemCache(),
        ]);

        $rulePackageRuleCache = new RulePackageRuleCache();

        $refObject = new ReflectionObject($rulePackageRuleCache);
        $refProperty = $refObject->getProperty('items');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackageRuleCache, $arrayCollection);

        $this->assertSame($itemToFind, $rulePackageRuleCache->findItem(self::EXAMPLE_UUID));
        $this->assertNull($rulePackageRuleCache->findItem('test123'));
    }

    public function testCannotFindItem()
    {
        $rulePackageRuleCache = new RulePackageRuleCache();

        $this->assertNull($rulePackageRuleCache->findItem('test123'));
    }

    public function testGetSpamRatingFactor()
    {
        $rulePackageRuleCache = new RulePackageRuleCache();
        $rulePackageRuleCache->setSpamRatingFactor(5);

        $this->assertEquals(5, $rulePackageRuleCache->getSpamRatingFactor());
    }

    public function testGetProject()
    {
        $project = new Project();

        $rulePackageRuleCache = new RulePackageRuleCache();
        $rulePackageRuleCache->setProject($project);

        $this->assertSame($project, $rulePackageRuleCache->getProject());
    }
}

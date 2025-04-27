<?php

namespace Mosparo\Tests\UnitTests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mosparo\Entity\Project;
use Mosparo\Entity\RulePackage;
use Mosparo\Entity\RulePackageCache;
use Mosparo\Entity\RulePackageRuleCache;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class RulePackageCacheTest extends TestCase
{
    const EXAMPLE_UUID = '00000000-0000-0000-0000-000000000000';

    public function testGetId()
    {
        $rulePackageCache = new RulePackageCache();

        $refObject = new ReflectionObject($rulePackageCache);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackageCache, '123');

        $this->assertEquals(123, $rulePackageCache->getId());
    }

    public function testGetRulePackage()
    {
        $rulePackage = new RulePackage();

        $rulePackageCache = new RulePackageCache();
        $rulePackageCache->setRulePackage($rulePackage);

        $this->assertSame($rulePackage, $rulePackageCache->getRulePackage());
    }

    public function testGetRefreshedAt()
    {
        $dateTime = new \DateTime();

        $rulePackageCache = new RulePackageCache();
        $rulePackageCache->setRefreshedAt($dateTime);

        $this->assertSame($dateTime, $rulePackageCache->getRefreshedAt());
    }

    public function testGetUpdatedAt()
    {
        $dateTime = new \DateTime();

        $rulePackageCache = new RulePackageCache();
        $rulePackageCache->setUpdatedAt($dateTime);

        $this->assertSame($dateTime, $rulePackageCache->getUpdatedAt());
    }

    public function testGetRefreshInterval()
    {
        $rulePackageCache = new RulePackageCache();
        $rulePackageCache->setRefreshInterval(86400);

        $this->assertEquals(86400, $rulePackageCache->getRefreshInterval());
    }

    public function testGetItems()
    {
        $arrayCollection = new ArrayCollection([
            new RulePackageRuleCache(),
            new RulePackageRuleCache()
        ]);

        $rulePackageCache = new RulePackageCache();

        $refObject = new ReflectionObject($rulePackageCache);
        $refProperty = $refObject->getProperty('rules');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackageCache, $arrayCollection);

        $this->assertSame($arrayCollection, $rulePackageCache->getRules());
    }

    public function testAddItem()
    {
        $arrayCollection = new ArrayCollection([
            new RulePackageRuleCache(),
            new RulePackageRuleCache(),
        ]);

        $rulePackageCache = new RulePackageCache();

        $refObject = new ReflectionObject($rulePackageCache);
        $refProperty = $refObject->getProperty('rules');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackageCache, $arrayCollection);

        $rulePackageCache->addRule(new RulePackageRuleCache);

        $this->assertCount(3, $rulePackageCache->getRules());
        $this->assertSame($arrayCollection, $rulePackageCache->getRules());
    }

    public function testRemoveItem()
    {
        $itemToRemove = new RulePackageRuleCache();
        $arrayCollection = new ArrayCollection([
            new RulePackageRuleCache(),
            $itemToRemove,
            new RulePackageRuleCache(),
        ]);

        $rulePackageCache = new RulePackageCache();

        $itemToRemove->setRulePackageCache($rulePackageCache);

        $refObject = new ReflectionObject($rulePackageCache);
        $refProperty = $refObject->getProperty('rules');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackageCache, $arrayCollection);

        $rulePackageCache->removeRule($itemToRemove);

        $this->assertCount(2, $rulePackageCache->getRules());
        $this->assertSame($arrayCollection, $rulePackageCache->getRules());
    }

    public function testFindItem()
    {
        $itemToFind = new RulePackageRuleCache();
        $itemToFind->setUuid(self::EXAMPLE_UUID);

        $arrayCollection = new ArrayCollection([
            new RulePackageRuleCache(),
            $itemToFind,
            new RulePackageRuleCache(),
        ]);

        $rulePackageCache = new RulePackageCache();

        $refObject = new ReflectionObject($rulePackageCache);
        $refProperty = $refObject->getProperty('rules');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackageCache, $arrayCollection);

        $this->assertSame($itemToFind, $rulePackageCache->findRule(self::EXAMPLE_UUID));
        $this->assertNull($rulePackageCache->findRule('test123'));
    }

    public function testCannotFindItem()
    {
        $rulePackageCache = new RulePackageCache();

        $this->assertNull($rulePackageCache->findRule('test123'));
    }

    public function testGetProject()
    {
        $project = new Project();

        $rulePackageCache = new RulePackageCache();
        $rulePackageCache->setProject($project);

        $this->assertSame($project, $rulePackageCache->getProject());
    }
}

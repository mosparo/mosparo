<?php

namespace Mosparo\Tests\UnitTests\Entity;

use Mosparo\Entity\Project;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Entity\RulePackageRuleItemCache;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class RulePackageRuleItemCacheTest extends TestCase
{
    const EXAMPLE_UUID = '00000000-0000-0000-0000-000000000000';

    public function testGetId()
    {
        $rulePackageRuleItemCache = new RulePackageRuleItemCache();

        $refObject = new ReflectionObject($rulePackageRuleItemCache);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackageRuleItemCache, '123');

        $this->assertEquals(123, $rulePackageRuleItemCache->getId());
    }

    public function testGetUuid()
    {
        $rulePackageRuleItemCache = new RulePackageRuleItemCache();
        $rulePackageRuleItemCache->setUuid(self::EXAMPLE_UUID);

        $this->assertEquals(self::EXAMPLE_UUID, $rulePackageRuleItemCache->getUuid());
    }

    public function testGetRulePackageRuleCache()
    {
        $rulePackageRuleCache = new RulePackageRuleCache();

        $rulePackageRuleItemCache = new RulePackageRuleItemCache();
        $rulePackageRuleItemCache->setRulePackageRuleCache($rulePackageRuleCache);

        $this->assertSame($rulePackageRuleCache, $rulePackageRuleItemCache->getRulePackageRuleCache());
    }

    public function testGetType()
    {
        $rulePackageRuleItemCache = new RulePackageRuleItemCache();
        $rulePackageRuleItemCache->setType('test-type');

        $this->assertEquals('test-type', $rulePackageRuleItemCache->getType());
    }

    public function testGetValue()
    {
        $rulePackageRuleItemCache = new RulePackageRuleItemCache();
        $rulePackageRuleItemCache->setValue(300);

        $this->assertEquals(300, $rulePackageRuleItemCache->getValue());
    }

    public function testGetSpamRatingFactor()
    {
        $rulePackageRuleItemCache = new RulePackageRuleItemCache();
        $rulePackageRuleItemCache->setSpamRatingFactor(5);

        $this->assertEquals(5, $rulePackageRuleItemCache->getSpamRatingFactor());
    }

    public function testGetProject()
    {
        $project = new Project();

        $rulePackageRuleItemCache = new RulePackageRuleItemCache();
        $rulePackageRuleItemCache->setProject($project);

        $this->assertSame($project, $rulePackageRuleItemCache->getProject());
    }
}

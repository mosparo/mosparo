<?php

namespace Mosparo\Tests\UnitTests\Entity;

use Mosparo\Entity\Project;
use Mosparo\Entity\RulesetRuleCache;
use Mosparo\Entity\RulesetRuleItemCache;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class RulesetRuleItemCacheTest extends TestCase
{
    const EXAMPLE_UUID = '00000000-0000-0000-0000-000000000000';

    public function testGetId()
    {
        $rulesetRuleItemCache = new RulesetRuleItemCache();

        $refObject = new ReflectionObject($rulesetRuleItemCache);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulesetRuleItemCache, '123');

        $this->assertEquals(123, $rulesetRuleItemCache->getId());
    }

    public function testGetUuid()
    {
        $rulesetRuleItemCache = new RulesetRuleItemCache();
        $rulesetRuleItemCache->setUuid(self::EXAMPLE_UUID);

        $this->assertEquals(self::EXAMPLE_UUID, $rulesetRuleItemCache->getUuid());
    }

    public function testGetRulesetRuleCache()
    {
        $rulesetRuleCache = new RulesetRuleCache();

        $rulesetRuleItemCache = new RulesetRuleItemCache();
        $rulesetRuleItemCache->setRulesetRuleCache($rulesetRuleCache);

        $this->assertSame($rulesetRuleCache, $rulesetRuleItemCache->getRulesetRuleCache());
    }

    public function testGetType()
    {
        $rulesetRuleItemCache = new RulesetRuleItemCache();
        $rulesetRuleItemCache->setType('test-type');

        $this->assertEquals('test-type', $rulesetRuleItemCache->getType());
    }

    public function testGetValue()
    {
        $rulesetRuleItemCache = new RulesetRuleItemCache();
        $rulesetRuleItemCache->setValue(300);

        $this->assertEquals(300, $rulesetRuleItemCache->getValue());
    }

    public function testGetSpamRatingFactor()
    {
        $rulesetRuleItemCache = new RulesetRuleItemCache();
        $rulesetRuleItemCache->setSpamRatingFactor(5);

        $this->assertEquals(5, $rulesetRuleItemCache->getSpamRatingFactor());
    }

    public function testGetProject()
    {
        $project = new Project();

        $rulesetRuleItemCache = new RulesetRuleItemCache();
        $rulesetRuleItemCache->setProject($project);

        $this->assertSame($project, $rulesetRuleItemCache->getProject());
    }
}

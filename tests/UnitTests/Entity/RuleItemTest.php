<?php

namespace Mosparo\Tests\UnitTests\Entity;

use Mosparo\Entity\Project;
use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class RuleItemTest extends TestCase
{
    const EXAMPLE_UUID = '00000000-0000-0000-0000-000000000000';

    public function testGetId()
    {
        $rulesetRuleItemCache = new RuleItem();

        $refObject = new ReflectionObject($rulesetRuleItemCache);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulesetRuleItemCache, '123');

        $this->assertEquals(123, $rulesetRuleItemCache->getId());
    }

    public function testGetUuid()
    {
        $rulesetRuleItemCache = new RuleItem();
        $rulesetRuleItemCache->setUuid(self::EXAMPLE_UUID);

        $this->assertEquals(self::EXAMPLE_UUID, $rulesetRuleItemCache->getUuid());
    }

    public function testGetRule()
    {
        $rulesetRuleCache = new Rule();

        $rulesetRuleItemCache = new RuleItem();
        $rulesetRuleItemCache->setRule($rulesetRuleCache);

        $this->assertSame($rulesetRuleCache, $rulesetRuleItemCache->getRule());
    }

    public function testGetType()
    {
        $rulesetRuleItemCache = new RuleItem();
        $rulesetRuleItemCache->setType('test-type');

        $this->assertEquals('test-type', $rulesetRuleItemCache->getType());
    }

    public function testGetValue()
    {
        $rulesetRuleItemCache = new RuleItem();
        $rulesetRuleItemCache->setValue(300);

        $this->assertEquals(300, $rulesetRuleItemCache->getValue());
    }

    public function testGetSpamRatingFactor()
    {
        $rulesetRuleItemCache = new RuleItem();
        $rulesetRuleItemCache->setSpamRatingFactor(5);

        $this->assertEquals(5, $rulesetRuleItemCache->getSpamRatingFactor());
    }

    public function testGetProject()
    {
        $project = new Project();

        $rulesetRuleItemCache = new RuleItem();
        $rulesetRuleItemCache->setProject($project);

        $this->assertSame($project, $rulesetRuleItemCache->getProject());
    }
}

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
        $rulePackageRuleItemCache = new RuleItem();

        $refObject = new ReflectionObject($rulePackageRuleItemCache);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackageRuleItemCache, '123');

        $this->assertEquals(123, $rulePackageRuleItemCache->getId());
    }

    public function testGetUuid()
    {
        $rulePackageRuleItemCache = new RuleItem();
        $rulePackageRuleItemCache->setUuid(self::EXAMPLE_UUID);

        $this->assertEquals(self::EXAMPLE_UUID, $rulePackageRuleItemCache->getUuid());
    }

    public function testGetRule()
    {
        $rulePackageRuleCache = new Rule();

        $rulePackageRuleItemCache = new RuleItem();
        $rulePackageRuleItemCache->setRule($rulePackageRuleCache);

        $this->assertSame($rulePackageRuleCache, $rulePackageRuleItemCache->getRule());
    }

    public function testGetType()
    {
        $rulePackageRuleItemCache = new RuleItem();
        $rulePackageRuleItemCache->setType('test-type');

        $this->assertEquals('test-type', $rulePackageRuleItemCache->getType());
    }

    public function testGetValue()
    {
        $rulePackageRuleItemCache = new RuleItem();
        $rulePackageRuleItemCache->setValue(300);

        $this->assertEquals(300, $rulePackageRuleItemCache->getValue());
    }

    public function testGetSpamRatingFactor()
    {
        $rulePackageRuleItemCache = new RuleItem();
        $rulePackageRuleItemCache->setSpamRatingFactor(5);

        $this->assertEquals(5, $rulePackageRuleItemCache->getSpamRatingFactor());
    }

    public function testGetProject()
    {
        $project = new Project();

        $rulePackageRuleItemCache = new RuleItem();
        $rulePackageRuleItemCache->setProject($project);

        $this->assertSame($project, $rulePackageRuleItemCache->getProject());
    }
}

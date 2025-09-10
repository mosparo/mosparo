<?php

namespace Mosparo\Tests\UnitTests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mosparo\Entity\Project;
use Mosparo\Entity\RulePackage;
use Mosparo\Entity\RulePackageCache;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class RulePackageTest extends TestCase
{
    public function testGetId()
    {
        $rulePackage = new RulePackage();

        $refObject = new ReflectionObject($rulePackage);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rulePackage, '123');

        $this->assertEquals(123, $rulePackage->getId());
    }

    public function testGetName()
    {
        $rulePackage = new RulePackage();
        $rulePackage->setName('test');

        $this->assertEquals('test', $rulePackage->getName());
    }

    public function testGetUrl()
    {
        $rulePackage = new RulePackage();
        $rulePackage->setSource('https://example.com/rules.json');

        $this->assertEquals('https://example.com/rules.json', $rulePackage->getSource());
    }

    public function testGetSpamRatingFactor()
    {
        $rulePackage = new RulePackage();
        $rulePackage->setSpamRatingFactor(5);

        $this->assertEquals(5, $rulePackage->getSpamRatingFactor());
    }

    public function testGetStatus()
    {
        $rulePackage = new RulePackage();
        $rulePackage->setStatus(true);

        $this->assertTrue($rulePackage->getStatus());
    }

    public function testGetRulePackageCache()
    {
        $rulePackageCache = new RulePackageCache();

        $rulePackage = new RulePackage();
        $rulePackage->setRulePackageCache($rulePackageCache);

        $this->assertSame($rulePackageCache, $rulePackage->getRulePackageCache());
    }

    public function testGetProject()
    {
        $project = new Project();

        $rulePackage = new RulePackage();
        $rulePackage->setProject($project);

        $this->assertSame($project, $rulePackage->getProject());
    }
}

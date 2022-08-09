<?php

namespace Mosparo\Tests\UnitTests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mosparo\Entity\Project;
use Mosparo\Entity\Ruleset;
use Mosparo\Entity\RulesetCache;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class RulesetTest extends TestCase
{
    public function testGetId()
    {
        $ruleset = new Ruleset();

        $refObject = new ReflectionObject($ruleset);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($ruleset, '123');

        $this->assertEquals(123, $ruleset->getId());
    }

    public function testGetName()
    {
        $ruleset = new Ruleset();
        $ruleset->setName('test');

        $this->assertEquals('test', $ruleset->getName());
    }

    public function testGetUrl()
    {
        $ruleset = new Ruleset();
        $ruleset->setUrl('https://example.com/rules.json');

        $this->assertEquals('https://example.com/rules.json', $ruleset->getUrl());
    }

    public function testGetSpamRatingFactor()
    {
        $ruleset = new Ruleset();
        $ruleset->setSpamRatingFactor(5);

        $this->assertEquals(5, $ruleset->getSpamRatingFactor());
    }

    public function testGetStatus()
    {
        $ruleset = new Ruleset();
        $ruleset->setStatus(true);

        $this->assertTrue($ruleset->getStatus());
    }

    public function testGetRulesetCache()
    {
        $rulesetCache = new RulesetCache();

        $ruleset = new Ruleset();
        $ruleset->setRulesetCache($rulesetCache);

        $this->assertSame($rulesetCache, $ruleset->getRulesetCache());
    }

    public function testGetProject()
    {
        $project = new Project();

        $ruleset = new Ruleset();
        $ruleset->setProject($project);

        $this->assertSame($project, $ruleset->getProject());
    }
}

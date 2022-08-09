<?php

namespace Mosparo\Tests\UnitTests\Entity;

use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectConfigValue;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class ProjectConfigValueTest extends TestCase
{
    public function testGetId()
    {
        $projectConfigValue = new ProjectConfigValue();

        $refObject = new ReflectionObject($projectConfigValue);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($projectConfigValue, '123');

        $this->assertEquals(123, $projectConfigValue->getId());
    }

    public function testGetName()
    {
        $projectConfigValue = new ProjectConfigValue();
        $projectConfigValue->setName('testConfigValue');

        $this->assertEquals('testConfigValue', $projectConfigValue->getName());
    }

    public function testGetValue()
    {
        $projectConfigValue = new ProjectConfigValue();
        $projectConfigValue->setValue(300);

        $this->assertEquals(300, $projectConfigValue->getValue());
    }

    public function testGetProject()
    {
        $project = new Project();

        $projectConfigValue = new ProjectConfigValue();
        $projectConfigValue->setProject($project);

        $this->assertSame($project, $projectConfigValue->getProject());
    }
}

<?php

namespace Mosparo\Tests\UnitTests\Entity;

use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\User;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class ProjectMemberTest extends TestCase
{
    public function testGetId()
    {
        $projectMember = new ProjectMember();

        $refObject = new ReflectionObject($projectMember);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($projectMember, '123');

        $this->assertEquals(123, $projectMember->getId());
    }

    public function testGetProject()
    {
        $project = new Project();

        $projectMember = new ProjectMember();
        $projectMember->setProject($project);

        $this->assertSame($project, $projectMember->getProject());
    }

    public function testGetUser()
    {
        $user = new User();

        $projectMember = new ProjectMember();
        $projectMember->setUser($user);

        $this->assertSame($user, $projectMember->getUser());
    }

    public function testGetRole()
    {
        $projectMember = new ProjectMember();
        $projectMember->setRole(ProjectMember::ROLE_OWNER);

        $this->assertEquals(ProjectMember::ROLE_OWNER, $projectMember->getRole());
    }
}

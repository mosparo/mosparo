<?php

namespace Mosparo\Tests\ApplicationTests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ExportCommandTest extends KernelTestCase
{
    const OWNER_EMAIL = 'export-owner@mosparo.test';

    const PROJECT_NAME = 'Exported project';

    const UUID = '00000000-0000-0000-0000-00000000e001';

    protected function createCommandTester(): CommandTester
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);

        return new CommandTester($application->find('mosparo:export'));
    }

    protected function createProject(): Project
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $project = $entityManager->getRepository(Project::class)->findOneBy(['uuid' => self::UUID]);
        if ($project !== null) {
            return $project;
        }

        $owner = new User();
        $owner->setEmail(self::OWNER_EMAIL);
        $owner->setPassword('hashedMosparoPassword');
        $owner->setRoles(['ROLE_USER']);

        $project = new Project();
        $project->setUuid(self::UUID);
        $project->setName(self::PROJECT_NAME);
        $project->setStatus(1);
        $project->setHosts(['export.mosparo.test']);
        $project->setPublicKey('exportPublicKey');
        $project->setPrivateKey('exportPrivateKey');

        $projectMember = new ProjectMember();
        $projectMember->setProject($project);
        $projectMember->setUser($owner);
        $projectMember->setRole(ProjectMember::ROLE_OWNER);

        $entityManager->persist($owner);
        $entityManager->persist($project);
        $entityManager->persist($projectMember);
        $entityManager->flush();

        return $project;
    }

    public function testExportProjectById(): void
    {
        $project = $this->createProject();
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute(['projectId' => (string) $project->getId()]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString(self::PROJECT_NAME, $commandTester->getDisplay());
    }

    public function testExportProjectByUuid(): void
    {
        $this->createProject();
        $commandTester = $this->createCommandTester();

        // A deployment knows the UUID of a project, because mosparo:project:create prints it and the ID is
        // not available anywhere outside the database.
        $returnCode = $commandTester->execute(['projectId' => self::UUID]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString(self::PROJECT_NAME, $commandTester->getDisplay());
    }

    public function testExportProjectWithUnknownUuid(): void
    {
        $this->createProject();
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute(['projectId' => '00000000-0000-0000-0000-00000000e999']);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('Project not found.', $commandTester->getDisplay());
    }

    public function testExportProjectWithInvalidIdentifier(): void
    {
        $this->createProject();
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute(['projectId' => 'not-an-identifier']);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('Project not found.', $commandTester->getDisplay());
    }
}

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

class CreateProjectCommandTest extends KernelTestCase
{
    const OWNER_EMAIL = 'project-owner@mosparo.test';

    const UUID = '00000000-0000-0000-0000-00000000c001';

    protected function createCommandTester(): CommandTester
    {
        $kernel = self::bootKernel();

        $this->createOwner();

        $application = new Application($kernel);

        return new CommandTester($application->find('mosparo:project:create'));
    }

    protected function createOwner(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        if ($entityManager->getRepository(User::class)->findOneBy(['email' => self::OWNER_EMAIL]) !== null) {
            return;
        }

        $owner = new User();
        $owner->setEmail(self::OWNER_EMAIL);
        $owner->setPassword('hashedMosparoPassword');
        $owner->setRoles(['ROLE_USER']);

        $entityManager->persist($owner);
        $entityManager->flush();
    }

    protected function findProject(string $name): ?Project
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getRepository(Project::class)->findOneBy(['name' => $name]);
    }

    public function testCreateProject(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Created project',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['mosparo.test', 'www.mosparo.test'],
            '--description' => 'The description of the project',
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString('created successfully', $commandTester->getDisplay());

        $project = $this->findProject('Created project');

        $this->assertNotNull($project);
        $this->assertEquals(['mosparo.test', 'www.mosparo.test'], $project->getHosts());
        $this->assertEquals('The description of the project', $project->getDescription());
        $this->assertEquals(1, $project->getStatus());
        $this->assertTrue(uuid_is_valid($project->getUuid()));
        $this->assertNotEmpty($project->getPublicKey());
        $this->assertNotEmpty($project->getPrivateKey());

        // The keys are needed to connect a website to the project, so the command has to output them.
        $this->assertStringContainsString($project->getPublicKey(), $commandTester->getDisplay());
        $this->assertStringContainsString($project->getPrivateKey(), $commandTester->getDisplay());

        $projectMember = self::getContainer()->get(EntityManagerInterface::class)->getRepository(ProjectMember::class)->findOneBy(['project' => $project]);

        $this->assertNotNull($projectMember);
        $this->assertEquals(self::OWNER_EMAIL, $projectMember->getUser()->getEmail());
        $this->assertEquals(ProjectMember::ROLE_OWNER, $projectMember->getRole());
    }

    public function testCreateProjectWithGivenUuidAndKeys(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project with given values',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['given.mosparo.test'],
            '--uuid' => self::UUID,
            '--public-key' => 'givenPublicKey',
            '--private-key' => 'givenPrivateKey',
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);

        $project = $this->findProject('Project with given values');

        $this->assertNotNull($project);
        $this->assertEquals(self::UUID, $project->getUuid());
        $this->assertEquals('givenPublicKey', $project->getPublicKey());
        $this->assertEquals('givenPrivateKey', $project->getPrivateKey());
    }

    public function testUpdateExistingProject(): void
    {
        $commandTester = $this->createCommandTester();

        $uuid = '00000000-0000-0000-0000-00000000c002';
        $commandTester->execute([
            'name' => 'Project before the update',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['before.mosparo.test'],
            '--uuid' => $uuid,
        ]);

        $returnCode = $commandTester->execute([
            'name' => 'Project after the update',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['after.mosparo.test'],
            '--uuid' => $uuid,
            '--public-key' => 'updatedPublicKey',
            '--private-key' => 'updatedPrivateKey',
            '--update' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString('updated successfully', $commandTester->getDisplay());

        $this->assertNull($this->findProject('Project before the update'));

        $project = $this->findProject('Project after the update');

        $this->assertNotNull($project);
        $this->assertEquals($uuid, $project->getUuid());
        $this->assertEquals(['after.mosparo.test'], $project->getHosts());
        $this->assertEquals('updatedPublicKey', $project->getPublicKey());

        // The owner is a member already, so the update does not add a second membership.
        $projectMembers = self::getContainer()->get(EntityManagerInterface::class)->getRepository(ProjectMember::class)->findBy(['project' => $project]);

        $this->assertCount(1, $projectMembers);
    }

    public function testCreateProjectFailsIfTheUuidIsUsedAlready(): void
    {
        $commandTester = $this->createCommandTester();

        $uuid = '00000000-0000-0000-0000-00000000c003';
        $commandTester->execute([
            'name' => 'Project with a used uuid',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['used.mosparo.test'],
            '--uuid' => $uuid,
        ]);

        $returnCode = $commandTester->execute([
            'name' => 'Second project with a used uuid',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['used.mosparo.test'],
            '--uuid' => $uuid,
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('exists already', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Second project with a used uuid'));
    }

    public function testCreateProjectFailsIfThePublicKeyIsUsedAlready(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project with a used public key',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['key.mosparo.test'],
            // The key of the project of the fixtures.
            '--public-key' => 'mosparoPublicKey',
            '--private-key' => 'otherPrivateKey',
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('public key', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Project with a used public key'));
    }

    public function testCreateProjectFailsWithoutAnOwner(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project without an owner',
            '--host' => ['owner.mosparo.test'],
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('--owner', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Project without an owner'));
    }

    public function testCreateProjectFailsWithAnUnknownOwner(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project with an unknown owner',
            '--owner' => 'unknown-user@mosparo.test',
            '--host' => ['owner.mosparo.test'],
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('was not found', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Project with an unknown owner'));
    }

    public function testCreateProjectFailsWithoutAHost(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project without a host',
            '--owner' => self::OWNER_EMAIL,
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('host is required', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Project without a host'));
    }

    public function testCreateProjectFailsWithAnInvalidHost(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project with an invalid host',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['https://mosparo.test'],
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('contains a protocol', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Project with an invalid host'));
    }

    public function testCreateProjectFailsWithAnInvalidUuid(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project with an invalid uuid',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['uuid.mosparo.test'],
            '--uuid' => 'not-a-uuid',
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('not a valid UUID', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Project with an invalid uuid'));
    }

    public function testCreateProjectFailsWithOnlyOneKey(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project with one key',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['keys.mosparo.test'],
            '--public-key' => 'onlyThePublicKey',
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('--private-key', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Project with one key'));
    }

    public function testUpdateFailsWithoutAUuid(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project updated without a uuid',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['update.mosparo.test'],
            '--update' => true,
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('--uuid', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Project updated without a uuid'));
    }
}

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

class ImportCommandTest extends KernelTestCase
{
    const OWNER_EMAIL = 'import-owner@mosparo.test';

    const PROJECT_NAME = 'Imported project';

    const UUID = '00000000-0000-0000-0000-00000000f001';

    protected function createCommandTester(string $command): CommandTester
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);

        return new CommandTester($application->find($command));
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
        $project->setHosts(['import.mosparo.test']);
        $project->setPublicKey('importPublicKey');
        $project->setPrivateKey('importPrivateKey');

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

    protected function exportProject(string $identifier): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'mosparo-import-test');

        $commandTester = $this->createCommandTester('mosparo:export');
        $returnCode = $commandTester->execute(['projectId' => $identifier, 'filePath' => $filePath]);

        $this->assertEquals(Command::SUCCESS, $returnCode);

        return $filePath;
    }

    public function testImportByUuid(): void
    {
        $this->createProject();

        // The export of the project is a valid import file for the same project, so the import finds no
        // changes. That is enough to show that the command addressed the project by its UUID, because an
        // unknown identifier stops the command before it reads the file.
        $filePath = $this->exportProject(self::UUID);

        $commandTester = $this->createCommandTester('mosparo:import');
        $returnCode = $commandTester->execute([
            'projectId' => self::UUID,
            'filePath' => $filePath,
            '--designSettings' => true,
            '--force' => true,
        ]);

        unlink($filePath);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString('no changes are available', $commandTester->getDisplay());
    }

    public function testImportWithUnknownUuid(): void
    {
        $this->createProject();
        $filePath = $this->exportProject(self::UUID);

        $commandTester = $this->createCommandTester('mosparo:import');
        $returnCode = $commandTester->execute([
            'projectId' => '00000000-0000-0000-0000-00000000f999',
            'filePath' => $filePath,
            '--designSettings' => true,
            '--force' => true,
        ]);

        unlink($filePath);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('Project not found.', $commandTester->getDisplay());
    }
}

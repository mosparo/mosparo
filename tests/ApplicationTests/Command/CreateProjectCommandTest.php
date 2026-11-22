<?php

namespace Mosparo\Tests\ApplicationTests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\User;
use Mosparo\Helper\DesignHelper;
use Mosparo\Helper\ProjectHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;

class CreateProjectCommandTest extends KernelTestCase
{
    const OWNER_EMAIL = 'project-owner@mosparo.test';

    const UUID = '00000000-0000-0000-0000-00000000c001';

    protected function tearDown(): void
    {
        // With built assets, the command writes the CSS of the project into public/resources/. The projects of the
        // test database exist only for the tests, so remove the CSS files and the mappings of all of them again.
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $projectHelper = self::getContainer()->get(ProjectHelper::class);
        $designHelper = self::getContainer()->get(DesignHelper::class);

        foreach ($entityManager->getRepository(Project::class)->findAll() as $project) {
            // The configuration values of a project can only be read while the project is active.
            $projectHelper->setActiveProject($project);
            $designHelper->clearCssCache($project);
        }

        $projectHelper->unsetActiveProject();

        parent::tearDown();
    }

    protected function createCommandTester(): CommandTester
    {
        $kernel = self::bootKernel();

        $this->createOwner();

        // The console enables the filter for the project related entities before it executes a command, which
        // CommandTester does not do because it runs the command without the event dispatcher of the console.
        self::getContainer()->get(ProjectHelper::class)->enableDoctrineFilter();

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

    protected function useEntrypointsFile(string $path): void
    {
        // Replace the Encore lookup of the default build, so the test does not depend on a built public/build directory.
        self::getContainer()->set('webpack_encore.entrypoint_lookup[_default]', new EntrypointLookup($path));
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

        $this->assertStringContainsString('(hidden)', $commandTester->getDisplay());
    }

    public function testCreateProjectWithGivenPrivateKeyFromStdin(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->setInputs(['givenStdinPrivateKey']);

        $returnCode = $commandTester->execute([
            'name' => 'Project with given private key from STDIN',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['given.mosparo.test'],
            '--public-key' => 'givenPublicKeyForStdinPrivateKey',
            '--private-key-from-stdin' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);

        $project = $this->findProject('Project with given private key from STDIN');

        $this->assertNotNull($project);
        $this->assertEquals('givenPublicKeyForStdinPrivateKey', $project->getPublicKey());
        $this->assertEquals('givenStdinPrivateKey', $project->getPrivateKey());

        $this->assertStringContainsString('(hidden)', $commandTester->getDisplay());
    }

    public function testUpdateExistingProject(): void
    {
        $commandTester = $this->createCommandTester();

        $uuid = '00000000-0000-0000-0000-00000000c002';
        $returnCode = $commandTester->execute([
            'name' => 'Project before the update',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['before.mosparo.test'],
            '--uuid' => $uuid,
        ]);
        $this->assertEquals(Command::SUCCESS, $returnCode);

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
        $this->assertStringContainsString('(hidden)', $commandTester->getDisplay());

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

    public function testUpdateExistingProjectWithoutKeys(): void
    {
        $commandTester = $this->createCommandTester();

        $uuid = '00000000-0000-0000-0000-00000000c003';
        $returnCode = $commandTester->execute([
            'name' => 'Project before the update without keys',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['before.mosparo.test'],
            '--uuid' => $uuid,
        ]);
        $this->assertEquals(Command::SUCCESS, $returnCode);

        $project = $this->findProject('Project before the update without keys');
        $publicKeyBeforeUpdate = $project->getPublicKey();
        $privateKeyBeforeUpdate = $project->getPrivateKey();

        $returnCode = $commandTester->execute([
            'name' => 'Project after the update without keys',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['after.mosparo.test'],
            '--uuid' => $uuid,
            '--update' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString('updated successfully', $commandTester->getDisplay());
        $this->assertStringContainsString('(hidden)', $commandTester->getDisplay());

        $this->assertNull($this->findProject('Project before the update without keys'));

        $project = $this->findProject('Project after the update without keys');

        $this->assertNotNull($project);
        $this->assertEquals($uuid, $project->getUuid());
        $this->assertEquals(['after.mosparo.test'], $project->getHosts());
        $this->assertEquals($publicKeyBeforeUpdate, $project->getPublicKey());
        $this->assertEquals($privateKeyBeforeUpdate, $project->getPrivateKey());
    }

    public function testCreateProjectFailsIfTheUuidIsUsedAlready(): void
    {
        $commandTester = $this->createCommandTester();

        $uuid = '00000000-0000-0000-0000-00000000c004';
        $returnCode = $commandTester->execute([
            'name' => 'Project with a used uuid',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['used.mosparo.test'],
            '--uuid' => $uuid,
        ]);
        $this->assertEquals(Command::SUCCESS, $returnCode);

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

    public function testCreateProjectFailsIfTheNameIsEmpty(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => '',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['used.mosparo.test'],
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('name: This value should not be blank.', $commandTester->getDisplay());
    }

    public function testCreateProjectFailsIfTheNameIsTooLong(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => str_repeat('a', 256),
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['used.mosparo.test'],
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('name: This value is too long. It should have 255 characters or less.', $commandTester->getDisplay());
    }

    public function testCreateProjectFailsIfTheKeysAreTooShort(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Test project with too short keys',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['used.mosparo.test'],
            '--public-key' => 'test',
            '--private-key' => 'test',
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('publicKey: This value is too short. It should have 10 characters or more.', $commandTester->getDisplay());
        $this->assertStringContainsString('privateKey: This value is too short. It should have 10 characters or more.', $commandTester->getDisplay());
    }

    public function testCreateProjectFailsIfThePublicKeyIsTooLong(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Test project with too long public key',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['used.mosparo.test'],
            '--public-key' => str_repeat('a', 65),
            '--private-key' => str_repeat('a', 65),
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('publicKey: This value is too long. It should have 64 characters or less.', $commandTester->getDisplay());
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

    public function testCreateProjectFailsWithBothPrivateKeyOptions(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project with both private key options',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['keys.mosparo.test'],
            '--public-key' => 'superPublicKey',
            '--private-key' => 'bothPrivateKeyOptions',
            '--private-key-from-stdin' => true,
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('Use either the --private-key option or --private-key-from-stdin, not both.', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Project with both private key options'));
    }

    public function testCreateProjectFailsWithPrivateKeyFromStdinWithoutInput(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project with private key from stdin without input',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['keys.mosparo.test'],
            '--public-key' => 'superPublicKey',
            '--private-key-from-stdin' => true,
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('No private key was found on the standard input.', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Project with private key from stdin without input'));
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

    public function testCreateProjectWithoutBuiltAssets(): void
    {
        $commandTester = $this->createCommandTester();
        $this->useEntrypointsFile(__DIR__ . '/../Fixtures/does-not-exist/entrypoints.json');

        $returnCode = $commandTester->execute([
            'name' => 'Project without assets',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['without-assets.mosparo.test'],
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString('were not prepared', $commandTester->getDisplay());
        $this->assertStringContainsString('Could not find the entrypoints file', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Project without assets')->getConfigValue('designConfigHash'));
    }

    public function testCreateAndUpdateProjectWithBuiltAssets(): void
    {
        $commandTester = $this->createCommandTester();
        $this->useEntrypointsFile(__DIR__ . '/../Fixtures/build/entrypoints.json');

        $uuid = '00000000-0000-0000-0000-00000000a001';
        $returnCode = $commandTester->execute([
            'name' => 'Project with assets',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['with-assets.mosparo.test'],
            '--uuid' => $uuid,
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringNotContainsString('were not prepared', $commandTester->getDisplay());

        $project = $this->findProject('Project with assets');
        $hash = $project->getConfigValue('designConfigHash');
        $this->assertNotEmpty($hash);

        // An update does not change the design settings, so the hash stays the same.
        $returnCode = $commandTester->execute([
            'name' => 'Project with assets',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['with-assets.mosparo.test'],
            '--uuid' => $uuid,
            '--update' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertSame($hash, $this->findProject('Project with assets')->getConfigValue('designConfigHash'));

        // The test writes into public/resources/, remove the CSS file and the mapping again.
        self::getContainer()->get(DesignHelper::class)->clearCssCache($this->findProject('Project with assets'));
    }

    public function testUpdateExistingProjectInASeparateRun(): void
    {
        $commandTester = $this->createCommandTester();
        $this->useEntrypointsFile(__DIR__ . '/../Fixtures/build/entrypoints.json');

        $uuid = '00000000-0000-0000-0000-00000000a002';
        $returnCode = $commandTester->execute([
            'name' => 'Project of a separate run',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['separate-run.mosparo.test'],
            '--uuid' => $uuid,
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);

        $project = $this->findProject('Project of a separate run');
        $designConfigHash = $project->getConfigValue('designConfigHash');
        $publicKey = $project->getPublicKey();
        $privateKey = $project->getPrivateKey();

        $this->assertNotEmpty($designConfigHash);

        // A deployment updates the project in another run of the command, which loads the project and its
        // configuration values from the database instead of reusing the objects of the creation.
        $commandTester = $this->createCommandTester();
        $this->useEntrypointsFile(__DIR__ . '/../Fixtures/build/entrypoints.json');

        $returnCode = $commandTester->execute([
            'name' => 'Project of a separate run',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['separate-run.mosparo.test', 'www.separate-run.mosparo.test'],
            '--uuid' => $uuid,
            '--update' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString('updated successfully', $commandTester->getDisplay());

        $project = $this->findProject('Project of a separate run');

        $this->assertEquals(['separate-run.mosparo.test', 'www.separate-run.mosparo.test'], $project->getHosts());
        $this->assertSame($designConfigHash, $project->getConfigValue('designConfigHash'));
        $this->assertEquals($publicKey, $project->getPublicKey());
        $this->assertEquals($privateKey, $project->getPrivateKey());

        // The test writes into public/resources/, remove the CSS file and the mapping again.
        self::getContainer()->get(DesignHelper::class)->clearCssCache($project);
    }

    public function testCreateProjectWithJsonFormat(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project in the json format',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['json.mosparo.test', 'www.json.mosparo.test'],
            '--uuid' => '00000000-0000-0000-0000-00000000c005',
            '--description' => 'The description of the project',
            '--format' => 'json',
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);

        $project = $this->findProject('Project in the json format');

        $this->assertNotNull($project);

        // The output has to be the object alone, since it is read by a script.
        $data = json_decode($commandTester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('created', $data['action']);
        $this->assertSame($project->getId(), $data['id']);
        $this->assertSame('00000000-0000-0000-0000-00000000c005', $data['uuid']);
        $this->assertSame('Project in the json format', $data['name']);
        $this->assertSame('The description of the project', $data['description']);
        $this->assertSame(['json.mosparo.test', 'www.json.mosparo.test'], $data['hosts']);
        $this->assertSame($project->getPublicKey(), $data['publicKey']);

        // The command generated the keys, so the private key is the only place where it is available.
        $this->assertSame($project->getPrivateKey(), $data['privateKey']);
    }

    public function testUpdateProjectWithJsonFormat(): void
    {
        $commandTester = $this->createCommandTester();

        $uuid = '00000000-0000-0000-0000-00000000c006';
        $returnCode = $commandTester->execute([
            'name' => 'Project before the json update',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['before.json.mosparo.test'],
            '--uuid' => $uuid,
        ]);
        $this->assertEquals(Command::SUCCESS, $returnCode);

        $returnCode = $commandTester->execute([
            'name' => 'Project after the json update',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['after.json.mosparo.test'],
            '--uuid' => $uuid,
            '--public-key' => 'jsonPublicKey',
            '--private-key' => 'jsonPrivateKey',
            '--update' => true,
            '--format' => 'json',
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);

        $data = json_decode($commandTester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('updated', $data['action']);
        $this->assertSame($uuid, $data['uuid']);
        $this->assertSame('Project after the json update', $data['name']);
        $this->assertSame(['after.json.mosparo.test'], $data['hosts']);
        $this->assertSame('jsonPublicKey', $data['publicKey']);

        // The private key was given, so it is not returned, as the table shows "(hidden)" in that case.
        $this->assertNull($data['privateKey']);

        $this->assertSame($uuid, $this->findProject('Project after the json update')->getUuid());
    }

    public function testCreateProjectWithJsonFormatWithoutBuiltAssets(): void
    {
        $commandTester = $this->createCommandTester();
        $this->useEntrypointsFile(__DIR__ . '/../Fixtures/does-not-exist/entrypoints.json');

        $returnCode = $commandTester->execute([
            'name' => 'Project in the json format without assets',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['no-assets.json.mosparo.test'],
            '--format' => 'json',
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);

        // The message about the missing frontend resources is not written in this format, so the values report it.
        $data = json_decode($commandTester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertFalse($data['frontendResourcesPrepared']);
        $this->assertNotNull($data['assetsError']);
    }

    public function testCreateProjectFailsWithAnUnsupportedFormat(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'name' => 'Project with an unsupported format',
            '--owner' => self::OWNER_EMAIL,
            '--host' => ['format.mosparo.test'],
            '--format' => 'yaml',
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('is not supported', $commandTester->getDisplay());
        $this->assertNull($this->findProject('Project with an unsupported format'));
    }
}

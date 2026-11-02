<?php

namespace Mosparo\Tests\ApplicationTests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserCommandTest extends KernelTestCase
{
    protected function createCommandTester(): CommandTester
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);

        return new CommandTester($application->find('mosparo:user:create'));
    }

    protected function findUser(string $email): ?User
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    public function testCreateUser(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => 'create-user@mosparo.test',
            '--password' => 'mosparoPassword',
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString('Created the user "create-user@mosparo.test"', $commandTester->getDisplay());

        $user = $this->findUser('create-user@mosparo.test');

        $this->assertNotNull($user);
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertTrue(self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'mosparoPassword'));
    }

    public function testCreateUserWithRoles(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => 'create-user-with-roles@mosparo.test',
            '--password' => 'mosparoPassword',
            '--role' => ['ROLE_ADMIN', 'ROLE_CAN_CREATE_PROJECTS'],
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);

        $user = $this->findUser('create-user-with-roles@mosparo.test');

        $this->assertNotNull($user);

        // ROLE_ADMIN includes ROLE_USER, otherwise the user could not sign in.
        $this->assertEqualsCanonicalizing(['ROLE_ADMIN', 'ROLE_CAN_CREATE_PROJECTS', 'ROLE_USER'], $user->getRoles());
    }

    public function testCreateUserWithPasswordFromStdin(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->setInputs(['mosparoStdinPassword']);

        $returnCode = $commandTester->execute([
            'email' => 'create-user-with-stdin@mosparo.test',
            '--password-from-stdin' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);

        $user = $this->findUser('create-user-with-stdin@mosparo.test');

        $this->assertNotNull($user);
        $this->assertTrue(self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'mosparoStdinPassword'));
    }

    public function testCreateUserFailsIfTheUserExists(): void
    {
        $commandTester = $this->createCommandTester();

        $commandTester->execute([
            'email' => 'existing-user@mosparo.test',
            '--password' => 'mosparoPassword',
        ]);

        $returnCode = $commandTester->execute([
            'email' => 'existing-user@mosparo.test',
            '--password' => 'otherMosparoPassword',
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('exists already', $commandTester->getDisplay());

        $user = $this->findUser('existing-user@mosparo.test');

        $this->assertTrue(self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'mosparoPassword'));
    }

    public function testUpdateExistingUser(): void
    {
        $commandTester = $this->createCommandTester();

        $commandTester->execute([
            'email' => 'update-user@mosparo.test',
            '--password' => 'mosparoPassword',
            '--role' => ['ROLE_ADMIN'],
        ]);

        $returnCode = $commandTester->execute([
            'email' => 'update-user@mosparo.test',
            '--password' => 'otherMosparoPassword',
            '--role' => ['ROLE_USER'],
            '--update' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString('Updated the user "update-user@mosparo.test"', $commandTester->getDisplay());

        $user = $this->findUser('update-user@mosparo.test');

        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertTrue(self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'otherMosparoPassword'));
    }

    public function testCreateUserFailsWithAnUnavailableRole(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => 'unavailable-role@mosparo.test',
            '--password' => 'mosparoPassword',
            '--role' => ['ROLE_SUPER_ADMIN'],
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('ROLE_SUPER_ADMIN', $commandTester->getDisplay());
        $this->assertNull($this->findUser('unavailable-role@mosparo.test'));
    }

    public function testCreateUserFailsWithATooShortPassword(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => 'short-password@mosparo.test',
            '--password' => 'short',
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('characters long', $commandTester->getDisplay());
        $this->assertNull($this->findUser('short-password@mosparo.test'));
    }

    public function testCreateUserFailsWithAnInvalidEmailAddress(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => 'no-email-address',
            '--password' => 'mosparoPassword',
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('email', $commandTester->getDisplay());
        $this->assertNull($this->findUser('no-email-address'));
    }

    public function testCreateUserFailsWithoutAPasswordAndWithoutAnInteractiveTerminal(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute(
            ['email' => 'no-password@mosparo.test'],
            ['interactive' => false]
        );

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('--password', $commandTester->getDisplay());
        $this->assertNull($this->findUser('no-password@mosparo.test'));
    }
}

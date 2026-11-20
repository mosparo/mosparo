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

        // Every role includes ROLE_USER, otherwise the user could not sign in.
        $this->assertEqualsCanonicalizing(['ROLE_ADMIN', 'ROLE_CAN_CREATE_PROJECTS', 'ROLE_USER'], $user->getRoles());
    }

    public function testCreateUserWithCreateProjectRole(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => 'create-user-with-create-project-role@mosparo.test',
            '--password' => 'mosparoPassword',
            '--role' => ['ROLE_CAN_CREATE_PROJECTS'],
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);

        $user = $this->findUser('create-user-with-create-project-role@mosparo.test');

        $this->assertNotNull($user);

        // Every role includes ROLE_USER, otherwise the user could not sign in.
        $this->assertEqualsCanonicalizing(['ROLE_CAN_CREATE_PROJECTS', 'ROLE_USER'], $user->getRoles());
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

        $returnCode = $commandTester->execute([
            'email' => 'existing-user@mosparo.test',
            '--password' => 'mosparoPassword',
        ]);
        $this->assertEquals(Command::SUCCESS, $returnCode);

        $returnCode = $commandTester->execute([
            'email' => 'existing-user@mosparo.test',
            '--password' => 'otherMosparoPassword',
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('exists already', $commandTester->getDisplay());

        $user = $this->findUser('existing-user@mosparo.test');

        $this->assertTrue(self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'mosparoPassword'));
    }

    public function testCreateUserFailsIfEmailAddressIsEmpty(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => '',
            '--password' => 'mosparoPassword',
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('email: This value should not be blank.', $commandTester->getDisplay());
        $this->assertNull($this->findUser(''));
    }

    public function testCreateUserFailsIfEmailAddressIsTooLong(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => str_repeat('a', 180) . '@mosparo.test',
            '--password' => 'mosparoPassword',
        ]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('email: This value is too long. It should have 180 characters or less.', $commandTester->getDisplay());
    }

    public function testUpdateExistingUser(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => 'update-user@mosparo.test',
            '--password' => 'mosparoPassword',
            '--role' => ['ROLE_ADMIN'],
        ]);
        $this->assertEquals(Command::SUCCESS, $returnCode);

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

    public function testUpdateExistingUserPasswordOnly(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => 'update-user-password-only@mosparo.test',
            '--password' => 'mosparoPassword',
            '--role' => ['ROLE_ADMIN'],
        ]);
        $this->assertEquals(Command::SUCCESS, $returnCode);

        $returnCode = $commandTester->execute([
            'email' => 'update-user-password-only@mosparo.test',
            '--password' => 'otherMosparoPassword',
            '--update' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString('Updated the user "update-user-password-only@mosparo.test"', $commandTester->getDisplay());

        $user = $this->findUser('update-user-password-only@mosparo.test');

        $this->assertEqualsCanonicalizing(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        $this->assertTrue(self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'otherMosparoPassword'));
    }

    public function testUpdateExistingUserRolesOnly(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => 'update-user-roles-only@mosparo.test',
            '--password' => 'mosparoPassword',
        ]);
        $this->assertEquals(Command::SUCCESS, $returnCode);

        $returnCode = $commandTester->execute([
            'email' => 'update-user-roles-only@mosparo.test',
            '--role' => ['ROLE_ADMIN'],
            '--update' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString('Updated the user "update-user-roles-only@mosparo.test"', $commandTester->getDisplay());

        $user = $this->findUser('update-user-roles-only@mosparo.test');

        $this->assertEqualsCanonicalizing(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        $this->assertTrue(self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'mosparoPassword'));
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

    public function testUpdateUserFailsWithoutAPasswordAndWithoutAnInteractiveTerminal(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => 'update-user-without-any-option-and-non-interactive@mosparo.test',
            '--password' => 'mosparoPassword',
        ]);
        $this->assertEquals(Command::SUCCESS, $returnCode);

        $returnCode = $commandTester->execute(
            [
                'email' => 'update-user-without-any-option-and-non-interactive@mosparo.test',
                '--update' => true,
            ],
            ['interactive' => false],
        );

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('--password', $commandTester->getDisplay());

        $user = $this->findUser('update-user-without-any-option-and-non-interactive@mosparo.test');

        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertTrue(self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'mosparoPassword'));
    }

    public function testUpdateUserWithoutOptionsButWithAnInteractiveTerminal(): void
    {
        $commandTester = $this->createCommandTester();

        $returnCode = $commandTester->execute([
            'email' => 'update-user-without-any-option-but-with-interactive@mosparo.test',
            '--password' => 'mosparoPassword',
        ]);
        $this->assertEquals(Command::SUCCESS, $returnCode);

        $commandTester->setInputs(['otherMosparoPassword']);
        $returnCode = $commandTester->execute(
            [
                'email' => 'update-user-without-any-option-but-with-interactive@mosparo.test',
                '--update' => true,
            ],
            ['interactive' => true],
        );

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString('Updated the user "update-user-without-any-option-but-with-interactive@mosparo.test"', $commandTester->getDisplay());

        $user = $this->findUser('update-user-without-any-option-but-with-interactive@mosparo.test');

        $this->assertTrue(self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'otherMosparoPassword'));
    }
}

<?php

namespace Mosparo\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'mosparo:user:create')]
class CreateUserCommand extends Command
{
    const AVAILABLE_ROLES = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_CAN_CREATE_PROJECTS'];

    const MINIMUM_PASSWORD_LENGTH = 8;

    const MAXIMUM_PASSWORD_LENGTH = 4096;

    protected EntityManagerInterface $entityManager;

    protected UserPasswordHasherInterface $userPasswordHasher;

    protected ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, ValidatorInterface $validator)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->validator = $validator;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates a user.')
            ->setHelp('The command creates a user without the setup wizard or the administration interface, so an installation can be prepared in an automated deployment.')
            ->addArgument('email', InputArgument::REQUIRED, 'The email address of the user, which is also the username.')

            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'The password of the user. The command asks for the password if neither this option nor --password-from-stdin is used.')
            ->addOption('password-from-stdin', null, InputOption::VALUE_NONE, 'Read the password from the standard input instead of the --password option.')
            ->addOption('role', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, sprintf('The role of the user, usable multiple times (available roles: %s). ROLE_USER is used if the option is not used, and ROLE_ADMIN always includes ROLE_USER.', implode(', ', self::AVAILABLE_ROLES)))
            ->addOption('update', null, InputOption::VALUE_NONE, 'Set the password and the roles of an existing user instead of stopping with an error.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $formatter = $this->getHelper('formatter');

        $email = $input->getArgument('email');
        $userRepository = $this->entityManager->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => $email]);
        $isNewUser = ($user === null);

        if (!$isNewUser && !$input->getOption('update')) {
            $output->writeln($formatter->formatBlock([sprintf('The user "%s" exists already. Use --update to set the password and the roles of this user.', $email)], 'error', true));

            return Command::FAILURE;
        }

        try {
            $roles = $this->determineRoles($input->getOption('role'));
            $password = $this->determinePassword($input, $output);
        } catch (\InvalidArgumentException $e) {
            $output->writeln($formatter->formatBlock([$e->getMessage()], 'error', true));

            return Command::FAILURE;
        }

        if ($isNewUser) {
            $user = new User();
            $user->setEmail($email);
        }

        $user->setRoles($roles);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, $password));

        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
            }

            $output->writeln($formatter->formatBlock($messages, 'error', true));

            return Command::FAILURE;
        }

        if ($isNewUser) {
            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();

        if ($isNewUser) {
            $message = sprintf('Created the user "%s" with the roles %s.', $email, implode(', ', $roles));
        } else {
            $message = sprintf('Updated the user "%s" with the roles %s.', $email, implode(', ', $roles));
        }

        $output->writeln($formatter->formatBlock([$message], 'info', true));

        return Command::SUCCESS;
    }

    protected function determineRoles(array $roles): array
    {
        if (!$roles) {
            return ['ROLE_USER'];
        }

        $invalidRoles = array_diff($roles, self::AVAILABLE_ROLES);
        if ($invalidRoles) {
            throw new \InvalidArgumentException(sprintf('The role(s) %s are not available. Available roles: %s.', implode(', ', $invalidRoles), implode(', ', self::AVAILABLE_ROLES)));
        }

        // An administrator without ROLE_USER cannot sign in, the administration interface combines both roles as well.
        if (in_array('ROLE_ADMIN', $roles) && !in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_values(array_unique($roles));
    }

    protected function determinePassword(InputInterface $input, OutputInterface $output): string
    {
        $password = $input->getOption('password');
        $passwordFromStdin = $input->getOption('password-from-stdin');

        if ($password !== null && $passwordFromStdin) {
            throw new \InvalidArgumentException('Use either the --password option or --password-from-stdin, not both.');
        }

        if ($passwordFromStdin) {
            $stream = ($input instanceof StreamableInputInterface) ? $input->getStream() : null;
            $password = fgets($stream ?? STDIN);

            if ($password === false) {
                throw new \InvalidArgumentException('No password was found on the standard input.');
            }

            // Remove the line break of the piped line, but keep spaces, which can be part of the password.
            $password = preg_replace('/\r?\n$/', '', $password);
        } else if ($password === null) {
            if (!$input->isInteractive()) {
                throw new \InvalidArgumentException('Use the --password option or --password-from-stdin if the command runs without an interactive terminal.');
            }

            $question = new Question('Password: ');
            $question->setHidden(true);

            $password = $this->getHelper('question')->ask($input, $output, $question);
        }

        if ($password === null || $password === '') {
            throw new \InvalidArgumentException('The password cannot be empty.');
        }

        // The password form counts the characters of the password as well, not the bytes.
        $passwordLength = mb_strlen($password);
        if ($passwordLength < self::MINIMUM_PASSWORD_LENGTH || $passwordLength > self::MAXIMUM_PASSWORD_LENGTH) {
            throw new \InvalidArgumentException(sprintf('The password must be between %d and %d characters long.', self::MINIMUM_PASSWORD_LENGTH, self::MAXIMUM_PASSWORD_LENGTH));
        }

        return $password;
    }
}

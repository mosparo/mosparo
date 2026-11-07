<?php

namespace Mosparo\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\User;
use Mosparo\Helper\DesignHelper;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Util\TokenGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'mosparo:project:create')]
class CreateProjectCommand extends Command
{
    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected DesignHelper $designHelper;

    protected ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper, DesignHelper $designHelper, ValidatorInterface $validator)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->designHelper = $designHelper;
        $this->validator = $validator;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates a project.')
            ->setHelp('The command creates a project without the administration interface, so an installation can be prepared in an automated deployment. The UUID and the keys are generated if they are not given, which is why the command prints them.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the project.')

            ->addOption('owner', null, InputOption::VALUE_REQUIRED, 'The email address of the user who becomes the owner of the project.')
            ->addOption('host', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A host which is allowed to use the project, usable multiple times.')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'The description of the project.')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'The UUID of the project. A random UUID is generated if the option is not used.')
            ->addOption('public-key', null, InputOption::VALUE_REQUIRED, 'The public key of the project, to be used together with --private-key or --private-key-from-stdin. Both keys are generated if the options are not used.')
            ->addOption('private-key', null, InputOption::VALUE_REQUIRED, 'The private key of the project, to be used together with --public-key. Visible in the process list, prefer --private-key-from-stdin.')
            ->addOption('private-key-from-stdin', null, InputOption::VALUE_NONE, 'Read the private key from the standard input instead of the --private-key option, to be used together with --public-key.')
            ->addOption('update', null, InputOption::VALUE_NONE, 'Set the name, the description, the hosts and the keys of the project with the given UUID instead of stopping with an error. Requires --uuid.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $formatter = $this->getHelper('formatter');

        $update = $input->getOption('update');
        $uuid = $input->getOption('uuid');
        $projectRepository = $this->entityManager->getRepository(Project::class);

        try {
            if ($uuid !== null && !uuid_is_valid($uuid)) {
                throw new \InvalidArgumentException(sprintf('The UUID "%s" is not a valid UUID.', $uuid));
            }

            if ($update && $uuid === null) {
                throw new \InvalidArgumentException('The option --update requires --uuid, since the UUID identifies the project to update.');
            }

            $project = ($uuid === null) ? null : $projectRepository->findOneBy(['uuid' => $uuid]);
            if ($project !== null && !$update) {
                throw new \InvalidArgumentException(sprintf('A project with the UUID "%s" exists already. Use --update to set the values of this project.', $uuid));
            }

            $owner = $this->determineOwner($input->getOption('owner'));
            $hosts = $this->prepareHosts($input->getOption('host'));
            [$publicKey, $privateKey] = $this->determineKeys($input, $project);

            $keysGenerated = ($project === null && $input->getOption('public-key') === null);

            // The API searches the project by its public key, so two projects with the same key would not work.
            $projectWithSamePublicKey = $projectRepository->findOneBy(['publicKey' => $publicKey]);
            if ($projectWithSamePublicKey !== null && $projectWithSamePublicKey !== $project) {
                throw new \InvalidArgumentException('Another project uses the given public key already.');
            }
        } catch (\InvalidArgumentException $e) {
            $output->writeln($formatter->formatBlock([$e->getMessage()], 'error', true));

            return Command::FAILURE;
        }

        $isNewProject = ($project === null);
        if ($isNewProject) {
            $project = new Project();
            $project->setStatus(1);

            if ($uuid !== null) {
                $project->setUuid($uuid);
            }
        }

        $project->setName($input->getArgument('name'));
        $project->setHosts($hosts);
        $project->setPublicKey($publicKey);
        $project->setPrivateKey($privateKey);

        $description = $input->getOption('description');
        if ($description !== null) {
            $project->setDescription($description);
        }

        $violations = $this->validator->validate($project);
        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
            }

            $output->writeln($formatter->formatBlock($messages, 'error', true));

            return Command::FAILURE;
        }

        if ($isNewProject) {
            $this->entityManager->persist($project);
        }

        // Project::isProjectMember() reads the collection of the project, which does not contain a membership
        // that was stored without the project entity, so ask the repository instead.
        $isProjectMember = !$isNewProject && $this->entityManager->getRepository(ProjectMember::class)->findOneBy(['project' => $project, 'user' => $owner]) !== null;

        if (!$isProjectMember) {
            $projectMember = new ProjectMember();
            $projectMember->setProject($project);
            $projectMember->setUser($owner);
            $projectMember->setRole(ProjectMember::ROLE_OWNER);

            $this->entityManager->persist($projectMember);
        }

        $this->entityManager->flush();

        // The command does not change the design settings, so the frontend resources are only prepared if the project
        // has none yet: a new project, or one that was created while the assets were missing.
        $assetsError = null;
        if (!$project->getConfigValue('designConfigHash')) {
            $this->projectHelper->setActiveProject($project);

            try {
                $this->designHelper->generateCssCache($project);
                $this->entityManager->flush();
            } catch (\InvalidArgumentException $e) {
                // Webpack Encore throws this exception if the assets are not built (missing or invalid entrypoints.json).
                // The hash stays empty, which the message below reports. Every other error must not be hidden.
                $assetsError = $e->getMessage();
            }
        }

        $action = $isNewProject ? 'created' : 'updated';
        $output->writeln($formatter->formatBlock([sprintf('The project was %s successfully.', $action)], 'info', true));

        $table = new Table($output);
        $table
            ->setHeaders(['Name', 'Value'])
            ->setRows([
                ['Name', $project->getName()],
                ['UUID', $project->getUuid()],
                ['Hosts', implode(', ', $project->getHosts())],
                ['Public key', $project->getPublicKey()],
                ['Private key', $keysGenerated ? $project->getPrivateKey() : '(hidden)'],
            ]);

        $table->render();

        if (!$project->getConfigValue('designConfigHash')) {
            $output->writeln('');
            $messages = ['The frontend resources of the project were not prepared, because the assets of the frontend are missing. Please build the assets and refresh the frontend resources afterwards.'];
            if ($assetsError !== null) {
                $messages[] = $assetsError;
            }

            $output->writeln($formatter->formatBlock($messages, 'comment', true));
        }

        return Command::SUCCESS;
    }

    protected function determineOwner(?string $email): User
    {
        if ($email === null) {
            throw new \InvalidArgumentException('The option --owner is required, a project without an owner cannot be managed in the administration interface.');
        }

        $owner = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($owner === null) {
            throw new \InvalidArgumentException(sprintf('The user "%s" was not found. The owner has to exist before the project is created.', $email));
        }

        return $owner;
    }

    protected function prepareHosts(array $hosts): array
    {
        if (!$hosts) {
            throw new \InvalidArgumentException('At least one host is required, the frontend API refuses a request from a host which is not allowed by the project.');
        }

        foreach ($hosts as $host) {
            // The same rules as in the project form, which only accepts a host without a protocol.
            if (preg_match('#^[a-z0-9]+://#', $host)) {
                throw new \InvalidArgumentException(sprintf('The host "%s" contains a protocol.', $host));
            }

            if (!preg_match('#^([a-z0-9\-\.\*\:]+)$#', $host)) {
                throw new \InvalidArgumentException(sprintf('The host "%s" contains an invalid character.', $host));
            }
        }

        return array_values(array_unique($hosts));
    }

    protected function determineKeys(InputInterface $input, ?Project $project): array
    {
        $publicKey = $input->getOption('public-key');
        $privateKey = $this->determinePrivateKey($input);

        if (($publicKey === null) !== ($privateKey === null)) {
            throw new \InvalidArgumentException('Use both --public-key and --private-key (or --private-key-from-stdin), or neither of them.');
        }

        if ($publicKey === null && $project !== null) {
            // If the project is updated, we reuse the existing public and private keys.
            return [$project->getPublicKey(), $project->getPrivateKey()];
        }

        if ($publicKey === null) {
            $tokenGenerator = new TokenGenerator();

            return [$tokenGenerator->generateToken(), $tokenGenerator->generateToken()];
        }

        return [$publicKey, $privateKey];
    }

    protected function determinePrivateKey(InputInterface $input): ?string
    {
        $privateKey = $input->getOption('private-key');
        $privateKeyFromStdin = $input->getOption('private-key-from-stdin');

        if ($privateKey !== null && $privateKeyFromStdin) {
            throw new \InvalidArgumentException('Use either the --private-key option or --private-key-from-stdin, not both.');
        }

        if ($privateKeyFromStdin) {
            $stream = ($input instanceof StreamableInputInterface) ? $input->getStream() : null;
            $privateKey = fgets($stream ?? STDIN);

            if ($privateKey === false) {
                throw new \InvalidArgumentException('No private key was found on the standard input.');
            }

            // Remove the line break of the piped line.
            $privateKey = preg_replace('/\r?\n$/', '', $privateKey);
        }

        return $privateKey;
    }
}

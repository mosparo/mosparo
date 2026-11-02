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
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mosparo:project:create')]
class CreateProjectCommand extends Command
{
    const MAXIMUM_PUBLIC_KEY_LENGTH = 64;

    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected DesignHelper $designHelper;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper, DesignHelper $designHelper)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->designHelper = $designHelper;
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
            ->addOption('public-key', null, InputOption::VALUE_REQUIRED, 'The public key of the project, to be used together with --private-key. Both keys are generated if the options are not used.')
            ->addOption('private-key', null, InputOption::VALUE_REQUIRED, 'The private key of the project, to be used together with --public-key.')
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
            $owner = $this->determineOwner($input->getOption('owner'));
            $hosts = $this->prepareHosts($input->getOption('host'));
            [$publicKey, $privateKey] = $this->determineKeys($input->getOption('public-key'), $input->getOption('private-key'));

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

        // The design settings of the project define the frontend resources, so prepare them like the
        // administration interface does after a project was created.
        $this->projectHelper->setActiveProject($project);

        try {
            $this->designHelper->generateCssCache($project);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            // The assets of the frontend are missing, which the message below reports.
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
                ['Private key', $project->getPrivateKey()],
            ]);

        $table->render();

        if (!$project->getConfigValue('designConfigHash')) {
            $output->writeln('');
            $output->writeln($formatter->formatBlock(['The frontend resources of the project were not prepared, because the assets of the frontend are missing. Please build the assets and refresh the frontend resources afterwards.'], 'comment', true));
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

    protected function determineKeys(?string $publicKey, ?string $privateKey): array
    {
        if (($publicKey === null) !== ($privateKey === null)) {
            throw new \InvalidArgumentException('Use both --public-key and --private-key, or neither of them.');
        }

        if ($publicKey === null) {
            $tokenGenerator = new TokenGenerator();

            return [$tokenGenerator->generateToken(), $tokenGenerator->generateToken()];
        }

        if ($publicKey === '' || $privateKey === '') {
            throw new \InvalidArgumentException('The keys cannot be empty.');
        }

        if (strlen($publicKey) > self::MAXIMUM_PUBLIC_KEY_LENGTH) {
            throw new \InvalidArgumentException(sprintf('The public key cannot be longer than %d characters.', self::MAXIMUM_PUBLIC_KEY_LENGTH));
        }

        return [$publicKey, $privateKey];
    }
}

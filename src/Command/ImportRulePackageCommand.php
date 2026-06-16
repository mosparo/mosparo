<?php

namespace Mosparo\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\RulePackage;
use Mosparo\Enum\RulePackageType;
use Mosparo\Exception;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\RulePackageHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mosparo:rule-package:import')]
class ImportRulePackageCommand extends Command
{
    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected RulePackageHelper $rulePackageHelper;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper, RulePackageHelper $rulePackageHelper)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->rulePackageHelper = $rulePackageHelper;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Imports the content into a rule package.')
            ->addArgument('projectId', InputArgument::REQUIRED, 'The ID of the project in which the rule package is created.')
            ->addArgument('rulePackageId', InputArgument::REQUIRED, 'The ID of the rule package in which you want to import the rule package.')

            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'File path to the rule package file.', false)
            ->addOption('input', 'i', InputOption::VALUE_NONE, 'Read the rule package content from the stdin.')
            ->addOption('hash', 's', InputOption::VALUE_REQUIRED, 'Specify the SHA256 hash of the content to verify the hash before importing the file.', false)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $formatter = $this->getHelper('formatter');

        $projectRepository = $this->entityManager->getRepository(Project::class);
        $project = $projectRepository->find($input->getArgument('projectId'));

        if (!$project) {
            $output->writeln($formatter->formatBlock([sprintf('Project "%s" not found.', $input->getArgument('projectId'))], 'error', true));
            return Command::FAILURE;
        }

        $this->projectHelper->setActiveProject($project);

        $rulePackageRepository = $this->entityManager->getRepository(RulePackage::class);
        $rulePackage = $rulePackageRepository->find($input->getArgument('rulePackageId'));

        if (!$rulePackage) {
            $output->writeln($formatter->formatBlock([sprintf('Rule package "%s" not found.', $input->getArgument('rulePackageId'))], 'error', true));
            return Command::FAILURE;
        }

        if ($rulePackage->getType() !== RulePackageType::MANUALLY_VIA_CLI) {
            $output->writeln($formatter->formatBlock([sprintf('Rule package type (%s) is not allowed.', $rulePackage->getType()->name)], 'error', true));
            return Command::FAILURE;
        }

        $filePath = null;
        $useStdIn = false;
        if ($input->hasOption('file') && trim($input->getOption('file')) !== '') {
            $filePath = realpath($input->getOption('file'));

            if (!$filePath || !is_readable($filePath)) {
                $output->writeln($formatter->formatBlock(['Rule package file not found.'], 'error', true));
                return Command::FAILURE;
            }
        }

        if ($input->hasOption('input')) {
            $useStdIn = (bool) $input->getOption('input');
        }

        if (!$filePath && !$useStdIn) {
            $output->writeln($formatter->formatBlock(['No rule package input defined. Use either -f or -i to specify the source.'], 'error', true));
            return Command::FAILURE;
        }

        $content = '';
        if ($filePath) {
            $content = file_get_contents($filePath);
        } else if ($useStdIn) {
            $content = file_get_contents('php://stdin');

            $filePath = tempnam(sys_get_temp_dir(), 'rp-' . $rulePackage->getId());
            file_put_contents($filePath, $content);
        }

        if ($input->hasOption('hash') && trim($input->getOption('hash'))) {
            if (hash('sha256', $content) !== $input->getOption('hash')) {
                $output->writeln($formatter->formatBlock(['The specified hash is invalid for the given content.'], 'error', true));
                return Command::FAILURE;
            }
        }

        if (!trim($content)) {
            $output->writeln($formatter->formatBlock(['Empty input source.'], 'error', true));
            return Command::FAILURE;
        }

        // Process the content
        try {
            $this->rulePackageHelper->validateAndProcessContent($rulePackage, $filePath, false);
        } catch (Exception $e) {
            $output->writeln($formatter->formatBlock([sprintf('An error occurred: %s', $e->getMessage())], 'error', true));
        }

        // Store the rule package cache
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
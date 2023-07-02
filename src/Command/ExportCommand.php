<?php

namespace Mosparo\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Helper\ExportHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command
{
    protected static $defaultName = 'mosparo:export';

    protected EntityManagerInterface $entityManager;

    protected ExportHelper $exportHelper;

    public function __construct(EntityManagerInterface $entityManager, ExportHelper $exportHelper)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->exportHelper = $exportHelper;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Exports the configuration of a project.')
            ->addArgument('projectId', InputArgument::REQUIRED, 'The ID of the project that should be exported.')
            ->addArgument('filePath', InputArgument::OPTIONAL, 'The file path to which the export should be stored.')

            ->addOption('generalSettings', null, InputOption::VALUE_NEGATABLE, 'Export the general settings of a project (name, description, hosts and spam score).', true)
            ->addOption('designSettings', null, InputOption::VALUE_NEGATABLE, 'Export the design settings of a project.', true)
            ->addOption('securitySettings', null, InputOption::VALUE_NEGATABLE, 'Export the security settings of a project.', true)
            ->addOption('rules', null, InputOption::VALUE_NEGATABLE, 'Export the rules of a project.', true)
            ->addOption('rulesets', null, InputOption::VALUE_NEGATABLE, 'Export the rulesets a project.', true)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $formatter = $this->getHelper('formatter');

        $projectRepository = $this->entityManager->getRepository(Project::class);
        $project = $projectRepository->find($input->getArgument('projectId'));

        if (!$project) {
            $output->writeln($formatter->formatBlock(['Project not found.'], 'error', true));
            return Command::FAILURE;
        }

        $export = $this->exportHelper->exportProject(
            $project,
            $input->getOption('generalSettings'),
            $input->getOption('designSettings'),
            $input->getOption('securitySettings'),
            $input->getOption('rules'),
            $input->getOption('rulesets')
        );

        if ($input->getArgument('filePath')) {
            $filePath = $input->getArgument('filePath');
            $path = realpath(dirname($filePath));

            if (!$path || !is_writable($path)) {
                $output->writeln($formatter->formatBlock(['File path is not writable.'], 'error', true));
                return Command::FAILURE;
            }

            file_put_contents($filePath, json_encode($export));

            $output->writeln($formatter->formatBlock(['Export completed.'], 'info', true));
        } else {
            $output->writeln(json_encode($export));
        }

        return Command::SUCCESS;
    }
}
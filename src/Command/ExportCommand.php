<?php

namespace Mosparo\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Exception\ExportException;
use Mosparo\Helper\ExportHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mosparo:export')]
class ExportCommand extends Command
{
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
            ->addOption('translations', null, InputOption::VALUE_NEGATABLE, 'Export the translations of a project.', true)
            ->addOption('submissionRules', null, InputOption::VALUE_NEGATABLE, 'Export the submission rules of a project.', true)
            ->addOption('fieldRules', null, InputOption::VALUE_NEGATABLE, 'Export the field rules of a project.', true)
            ->addOption('rulePackages', null, InputOption::VALUE_NEGATABLE, 'Export the rule packages of a project.', true)
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

        try {
            $export = $this->exportHelper->exportProject(
                $project,
                $input->getOption('generalSettings'),
                $input->getOption('designSettings'),
                $input->getOption('securitySettings'),
                $input->getOption('translations'),
                $input->getOption('submissionRules'),
                $input->getOption('fieldRules'),
                $input->getOption('rulePackages')
            );
        } catch (ExportException $e) {
            $output->writeln($formatter->formatBlock([$e->getMessage()], 'error', true));
            return Command::FAILURE;
        }

        $jsonContent = json_encode($export);
        if ($jsonContent === false) {
            $output->writeln($formatter->formatBlock(['Could not encode the data to a valid JSON.'], 'error', true));
            return Command::FAILURE;
        }

        if ($input->getArgument('filePath')) {
            $filePath = $input->getArgument('filePath');
            $path = realpath(dirname($filePath));

            if (!$path || !is_writable($path)) {
                $output->writeln($formatter->formatBlock(['File path is not writable.'], 'error', true));
                return Command::FAILURE;
            }

            $result = file_put_contents($filePath, $jsonContent);
            if ($result === false) {
                $output->writeln($formatter->formatBlock(['Could not write to the given file path.'], 'error', true));
                return Command::FAILURE;
            }

            $output->writeln($formatter->formatBlock(['Export completed.'], 'info', true));
        } else {
            $output->writeln($jsonContent);
        }

        return Command::SUCCESS;
    }
}
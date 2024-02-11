<?php

namespace Mosparo\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Exception\ImportException;
use Mosparo\Helper\ImportHelper;
use Mosparo\Rule\RuleTypeManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(name: 'mosparo:import')]
class ImportCommand extends Command
{
    protected EntityManagerInterface $entityManager;

    protected TranslatorInterface $translator;

    protected ImportHelper $importHelper;

    protected RuleTypeManager $ruleTypeManager;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator, ImportHelper $importHelper, RuleTypeManager $ruleTypeManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->importHelper = $importHelper;
        $this->ruleTypeManager = $ruleTypeManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Imports the configuration from a file into a project.')
            ->addArgument('projectId', InputArgument::REQUIRED, 'The ID of the project to which the configuration will be imported.')
            ->addArgument('filePath', InputArgument::REQUIRED, 'The file path of the import file.')

            ->addOption('generalSettings', null, InputOption::VALUE_NEGATABLE, 'Import the general settings of a project (name, description, hosts and spam score).', false)
            ->addOption('designSettings', null, InputOption::VALUE_NEGATABLE, 'Import the design settings of a project.', false)
            ->addOption('securitySettings', null, InputOption::VALUE_NEGATABLE, 'Import the security settings of a project.', false)
            ->addOption('rules', null, InputOption::VALUE_NEGATABLE, 'Import the rules of a project.', false)
            ->addOption('handlingExistingRules', null, InputOption::VALUE_REQUIRED, 'Define what should happen with existing rules (acceptable values: "override", "append", or "add").', 'override')
            ->addOption('rulesets', null, InputOption::VALUE_NEGATABLE, 'Import the rulesets a project.', false)

            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the import without showing the summary or waiting for confirmation.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');

        $io = new SymfonyStyle($input, $output);
        $question = $this->getHelper('question');
        $formatter = $this->getHelper('formatter');

        $projectRepository = $this->entityManager->getRepository(Project::class);
        $project = $projectRepository->find($input->getArgument('projectId'));

        if (!$project) {
            $output->writeln($formatter->formatBlock(['Project not found.'], 'error', true));
            return Command::FAILURE;
        }

        $filePath = realpath($input->getArgument('filePath'));
        if (!$filePath || !is_readable($filePath)) {
            $output->writeln($formatter->formatBlock(['Import file not found.'], 'error', true));
            return Command::FAILURE;
        }

        $generalSettings = $input->getOption('generalSettings');
        $designSettings = $input->getOption('designSettings');
        $securitySettings = $input->getOption('securitySettings');
        $rules = $input->getOption('rules');
        $handlingExistingRules = $input->getOption('handlingExistingRules');
        $rulesets = $input->getOption('rulesets');


        if (!$generalSettings && !$designSettings && !$securitySettings && !$rules && !$rulesets) {
            $output->writeln($formatter->formatBlock(['Enable at least one section to import.'], 'error', true));
            return Command::FAILURE;
        }


        if ($rules) {
            if (!in_array($handlingExistingRules, ['override', 'append', 'add'])) {
                $output->writeln($formatter->formatBlock(['Invalid value for handlingExistingRules.'], 'error', true));
                return Command::FAILURE;
            }
        }

        $importData = [
            'projectId' => $project->getId(),
            'importDataFilename' => $filePath,
            'importGeneralSettings' => $generalSettings,
            'importDesignSettings' => $designSettings,
            'importSecuritySettings' => $securitySettings,
            'importRules' => $rules,
            'importRulesets' => $rulesets,
            'handlingExistingRules' => $handlingExistingRules,
        ];

        try {
            [$jobData, $importData, $hasChanges, $changes] = $this->importHelper->simulateImport(null, $importData);
        } catch (ImportException $e) {
            $output->writeln($formatter->formatBlock([$e->getMessage()], 'error', true));
            return Command::FAILURE;
        }

        if (!$hasChanges) {
            $output->writeln($formatter->formatBlock(['The project is already the same as the data in the import file, and no changes are available.'], 'info', true));
            return Command::SUCCESS;
        }

        if (!$force) {
            $sections = [
                'generalSettings' => ['number' => '1', 'name' => 'General settings', 'active' => $generalSettings, 'inFile' => isset($importData['project']['name'])],
                'designSettings' => ['number' => '2', 'name' => 'Design settings', 'active' => $designSettings, 'inFile' => isset($importData['project']['design'])],
                'securitySettings' => ['number' => '3', 'name' => 'Security settings', 'active' => $securitySettings, 'inFile' => isset($importData['project']['security'])],
                'rules' => ['number' => '4', 'name' => 'Rules', 'active' => $rules, 'inFile' => isset($importData['project']['rules'])],
                'rulesets' => ['number' => '5', 'name' => 'Rulesets', 'active' => $rulesets, 'inFile' => isset($importData['project']['rulesets'])],
            ];

            $availableCommands = [
                'summary' => 'Show the summary of the available changes.',
                '1' => 'Show the general setting changes.',
                '2' => 'Show the design setting changes.',
                '3' => 'Show the security setting changes.',
                '4' => 'Show the rule changes.',
                '5' => 'Show the ruleset changes.',
                'abort' => 'Abort the import.',
                'exit' => 'Abort the import.',
                'execute' => 'Execute the changes.',
                'help' => 'Show this summary.',
            ];
            $actionQuestion = new Question('Enter the section number to see the changes or "execute" to execute the changes: ', 'summary');
            $actionQuestion->setAutocompleterValues(array_keys($availableCommands));

            $answer = '';
            while (true) {
                if ($answer === '' || $answer == 'summary') {
                    $io->section('Available changes');

                    $output->writeln('The simulation of the import resulted in the following changes:');
                    $output->writeln('');

                    $rows = [];
                    foreach ($sections as $sectionKey => $sectionConfig) {
                        $sectionChanges = $changes[$sectionKey] ?? [];

                        if ($sectionKey === 'securitySettings') {
                            $sectionChanges += ($changes['securityGuidelines'] ?? []);
                        }

                        if ($sectionChanges) {
                            $result = count($sectionChanges) . ' Changes';
                        } else {
                            if (!$sectionConfig['active']) {
                                $result = '<info>This section is not active for this import.</info>';
                            } else if (!$sectionConfig['inFile']) {
                                $result = '<info>This section is not available in the import file.</info>';
                            } else {
                                $result = '<info>For this section are no changes required since everything is up to date already.</info>';
                            }
                        }

                        $rows[] = [
                            $sectionConfig['number'],
                            $sectionConfig['name'],
                            $result,
                        ];
                    }

                    $table = new Table($output);
                    $table
                        ->setHeaders(['Number', 'Section', 'Result'])
                        ->setRows($rows);

                    $table->render();
                } else if (is_numeric($answer) && in_array($answer, array_keys($availableCommands))) {
                    foreach ($sections as $sectionKey => $sectionConfig) {
                        if ($sectionConfig['number'] == $answer) {
                            $this->displayChangesTable(
                                $io,
                                $output,
                                $formatter,
                                $changes[$sectionKey] ?? [],
                                $sectionKey,
                                $sectionConfig['name'],
                                $sectionConfig['active'],
                                $sectionConfig['inFile']
                            );

                            if ($sectionKey === 'securitySettings' && isset($changes['securityGuidelines']) && !empty($changes['securityGuidelines'])) {
                                $output->writeln('');

                                $this->displayChangesTable(
                                    $io,
                                    $output,
                                    $formatter,
                                    $changes['securityGuidelines'] ?? [],
                                    'securityGuidelines',
                                    'Origin-based security guidelines',
                                    $sectionConfig['active'],
                                    $sectionConfig['inFile']
                                );
                            }

                            break;
                        }
                    }
                } else if ($answer === 'help') {
                    $io->section('Available commands');

                    $table = new Table($output);
                    $table->setHeaders(['Command', 'Description']);
                    $table->setRows($this->convertCommands($availableCommands));

                    $table->render();
                } else {
                    $output->writeln($formatter->formatBlock([sprintf('Command "%s" not valid.', $answer)], 'error', true));
                }

                $output->writeln('');

                $answer = $question->ask($input, $output, $actionQuestion);

                $output->writeln('');

                if (in_array($answer, ['abort', 'exit'])) {
                    // Abort if the user don't want to continue
                    return Command::SUCCESS;
                } else if ($answer === 'execute') {
                    break;
                }
            }
        }

        /**
         * Execute the changes
         */
        try {
            $jobData['changes'] = $changes;
            $this->importHelper->executeImport(null, $jobData);
        } catch (ImportException $e) {
            $output->writeln($formatter->formatBlock([$e->getMessage()], 'error', true));
            return Command::FAILURE;
        }

        $output->writeln($formatter->formatBlock(['The changes were successfully executed.'], 'info', true));

        return Command::SUCCESS;
    }

    protected function displayChangesTable(SymfonyStyle $io, OutputInterface $output, FormatterHelper $formatter, array $changes, string $sectionKey, string $name, bool $isActive, bool $inFile)
    {
        $io->section($name);

        if ($changes) {
            $table = new Table($output);

            if (in_array($sectionKey, ['generalSettings', 'designSettings', 'securitySettings'])) {
                $headers = ['Name', 'Old value', 'New value'];

                $values = [];
                foreach ($changes as $change) {
                    $oldValue = $this->prepareSetting($change['oldValue']);
                    $newValue = $this->prepareSetting($change['newValue']);
                    $values[] = [
                        $change['key'],
                        $oldValue,
                        $newValue,
                    ];
                }
            } else if ($sectionKey === 'securityGuidelines') {
                $headers = ['Action', 'Name', 'Priority', 'Criteria', 'Security settings'];

                $values = [];
                foreach ($changes as $change) {
                    $modeCriteria = '';
                    $modeSecuritySettings = '';

                    if ($change['mode'] === 'add') {
                        $mode = 'Add';

                        $modeCriteria = $modeSecuritySettings = 'New guideline';
                    } else if ($change['mode'] === 'modify') {
                        $mode = 'Modify';

                        if ($change['changedCriteria']) {
                            $modeCriteria = 'Modification required';
                        } else {
                            $modeCriteria = 'No modification required';
                        }

                        if ($change['changedSettings']) {
                            $modeSecuritySettings = 'Modification required';
                        } else {
                            $modeSecuritySettings = 'No modification required';
                        }
                    }

                    $values[] = [
                        $mode,
                        $change['importedGuideline']['name'],
                        $change['importedGuideline']['priority'],
                        $modeCriteria,
                        $modeSecuritySettings,
                    ];
                }
            } else if ($sectionKey === 'rules') {
                $headers = ['Action', 'Name', 'Type', 'Add items', 'Modify items', 'Remove items'];

                $values = [];
                foreach ($changes as $change) {
                    if ($change['mode'] === 'add') {
                        $mode = 'Add';
                    } else if ($change['mode'] === 'modify') {
                        $mode = 'Modify';
                    }

                    $ruleTypeName = $change['importedRule']['type'];
                    $ruleType = $this->ruleTypeManager->getRuleType($change['importedRule']['type']);
                    if ($ruleType) {
                        $ruleTypeName = $this->translator->trans($ruleType->getName(), [], 'mosparo', 'en');
                    }

                    $values[] = [
                        $mode,
                        $change['importedRule']['name'],
                        $ruleTypeName,
                        count($change['itemChanges']['add']),
                        count($change['itemChanges']['modify']),
                        count($change['itemChanges']['remove']),
                    ];
                }
            } else if ($sectionKey === 'rulesets') {
                $headers = ['Action', 'Name', 'URL', 'Status', 'Spam rating factor'];

                $values = [];
                foreach ($changes as $change) {
                    if ($change['mode'] === 'add') {
                        $mode = 'Add';
                    } else if ($change['mode'] === 'modify') {
                        $mode = 'Modify';
                    }

                    $status = 'Inactive';
                    if ($change['importedRuleset']['status']) {
                        $status = 'Active';
                    }

                    $values[] = [
                        $mode,
                        $change['importedRuleset']['name'],
                        $change['importedRuleset']['url'],
                        $status,
                        $change['importedRuleset']['spamRatingFactor'],
                    ];
                }
            }

            $table->setHeaders($headers);
            $table->setRows($values);

            $table->render();
        } else {
            if (!$isActive) {
                $result = 'This section is not active for this import.';
            } else if (!$inFile) {
                $result = 'This section is not available in the import file.';
            } else {
                $result = 'For this section are no changes required since everything is up to date already.';
            }

            $output->writeln($formatter->formatBlock([$result], 'info', true));
        }
    }

    protected function prepareSetting($value)
    {
        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n/', $value);
        }

        if (is_array($value)) {
            return implode(PHP_EOL, $value);
        }

        return $value;
    }

    protected function convertCommands(array $availableCommands): array
    {
        $commands = [];
        foreach ($availableCommands as $command => $description) {
            $commands[] = [
                $command,
                $description,
            ];
        }

        return $commands;
    }
}
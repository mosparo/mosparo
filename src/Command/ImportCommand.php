<?php

namespace Mosparo\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Attributes\RulePackageTypeInfo;
use Mosparo\Entity\Project;
use Mosparo\Enum\RulePackageType;
use Mosparo\Exception\ImportException;
use Mosparo\Helper\ImportHelper;
use Mosparo\Rules\FieldRule\RuleTypeManager;
use Mosparo\Rules\SubmissionRule\SubmissionRuleManager;
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

    protected SubmissionRuleManager $submissionRuleManager;

    protected RuleTypeManager $ruleTypeManager;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator, ImportHelper $importHelper, SubmissionRuleManager $submissionRuleManager, RuleTypeManager $ruleTypeManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->importHelper = $importHelper;
        $this->submissionRuleManager = $submissionRuleManager;
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
            ->addOption('translations', null, InputOption::VALUE_NEGATABLE, 'Import the translations of a project.', false)
            ->addOption('submissionRules', null, InputOption::VALUE_NEGATABLE, 'Import the submission rules of a project.', false)
            ->addOption('fieldRules', null, InputOption::VALUE_NEGATABLE, 'Import the field rules of a project.', false)
            ->addOption('handlingExistingFieldRules', null, InputOption::VALUE_REQUIRED, 'Define what should happen with existing field rules (acceptable values: "override", "append", or "add").', 'override')
            ->addOption('rulePackages', null, InputOption::VALUE_NEGATABLE, 'Import the rule packages of a project.', false)

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
        $translations = $input->getOption('translations');
        $submissionRules = $input->getOption('submissionRules');
        $fieldRules = $input->getOption('fieldRules');
        $handlingExistingFieldRules = $input->getOption('handlingExistingFieldRules');
        $rulePackages = $input->getOption('rulePackages');


        if (!$generalSettings && !$designSettings && !$securitySettings && !$translations && !$submissionRules && !$fieldRules && !$rulePackages) {
            $output->writeln($formatter->formatBlock(['Enable at least one section to import.'], 'error', true));
            return Command::FAILURE;
        }


        if ($fieldRules) {
            if (!in_array($handlingExistingFieldRules, ['override', 'append', 'add'])) {
                $output->writeln($formatter->formatBlock(['Invalid value for handlingExistingFieldRules.'], 'error', true));
                return Command::FAILURE;
            }
        }

        $importData = [
            'projectId' => $project->getId(),
            'importDataFilename' => $filePath,
            'importGeneralSettings' => $generalSettings,
            'importDesignSettings' => $designSettings,
            'importSecuritySettings' => $securitySettings,
            'importTranslations' => $translations,
            'importSubmissionRules' => $submissionRules,
            'importFieldRules' => $fieldRules,
            'importRulePackages' => $rulePackages,
            'handlingExistingFieldRules' => $handlingExistingFieldRules,
        ];

        try {
            [$jobData, $importData, $hasChanges, $changes, $notInImport] = $this->importHelper->simulateImport(null, $project, $importData);
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
                'translations' => ['number' => '4', 'name' => 'Translations', 'active' => $translations, 'inFile' => isset($importData['project']['translations'])],
                'submissionRules' => ['number' => '5', 'name' => 'Submission rules', 'active' => $submissionRules, 'inFile' => isset($importData['project']['submissionRules'])],
                'fieldRules' => ['number' => '6', 'name' => 'Field rules', 'active' => $fieldRules, 'inFile' => isset($importData['project']['fieldRules'])],
                'rulePackages' => ['number' => '7', 'name' => 'Rule packages', 'active' => $rulePackages, 'inFile' => isset($importData['project']['rulePackages'])],
            ];

            $availableCommands = [
                'summary' => 'Show the summary of the available changes.',
                '1' => 'Show the general setting changes.',
                '2' => 'Show the design setting changes.',
                '3' => 'Show the security setting changes.',
                '4' => 'Show the translation changes.',
                '5' => 'Show the submission rule changes.',
                '6' => 'Show the field rule changes.',
                '7' => 'Show the rule package changes.',
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
            $refreshRulePackages = $this->importHelper->executeImport(null, $project, $jobData);
        } catch (ImportException $e) {
            $output->writeln($formatter->formatBlock([$e->getMessage()], 'error', true));
            return Command::FAILURE;
        }

        $output->writeln($formatter->formatBlock(['The changes were successfully executed.'], 'info', true));

        if ($refreshRulePackages) {
            $output->writeln($formatter->formatBlock(['The import adjusted at least one rule package.', 'You should update the rule packages by executing `mosparo:rule-package:refresh`.'], 'comment', true));
        }

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
            } else if ($sectionKey === 'translations') {
                $headers = ['Action', 'Locale', 'Base text', 'Translated text'];

                $values = [];
                foreach ($changes as $change) {
                    if ($change['mode'] === 'add') {
                        $mode = 'Add';
                    } else if ($change['mode'] === 'modify') {
                        $mode = 'Modify';
                    }

                    $values[] = [
                        $mode,
                        $change['importedTranslation']['locale'],
                        $change['importedTranslation']['translationKey']->trans($this->translator),
                        $change['importedTranslation']['text'],
                    ];
                }
            } else if ($sectionKey === 'submissionRules') {
                $headers = ['Action', 'Name', 'Status', 'Rating', 'Modify values'];

                $values = [];
                foreach ($changes as $change) {
                    $submissionRule = $this->submissionRuleManager->getRule($change['importedRule']['key']);
                    if (!$submissionRule) {
                        continue;
                    }

                    $values[] = [
                        'Modify',
                        $this->translator->trans($submissionRule->getName(), [], 'mosparo'),
                        $change['importedRule']['enabled'] ? 'Enabled' : 'Disabled',
                        $change['importedRule']['rating'],
                        $change['modifyConfigValues'] ? 'Yes' : 'No',
                    ];
                }
            } else if ($sectionKey === 'fieldRules') {
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
            } else if ($sectionKey === 'rulePackages') {
                $headers = ['Action', 'Name', 'Type', 'URL', 'Status', 'Spam rating factor'];

                $values = [];
                foreach ($changes as $change) {
                    if ($change['mode'] === 'add') {
                        $mode = 'Add';
                    } else if ($change['mode'] === 'modify') {
                        $mode = 'Modify';
                    }

                    $status = 'Inactive';
                    if ($change['importedRulePackage']['status']) {
                        $status = 'Active';
                    }

                    $values[] = [
                        $mode,
                        $change['importedRulePackage']['name'],
                        $this->translator->trans(RulePackageTypeInfo::from(RulePackageType::from($change['importedRulePackage']['type']))->title, [], 'mosparo', 'en'),
                        $change['importedRulePackage']['source'] ?? 'Manual import',
                        $status,
                        $change['importedRulePackage']['spamRatingFactor'],
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
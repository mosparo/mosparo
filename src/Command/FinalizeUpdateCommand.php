<?php

namespace Mosparo\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\DesignHelper;
use Mosparo\Helper\Migration\OptimizeRuleItemsAfterUpdateHelper;
use Mosparo\Helper\Migration\V14OptimizeRulesHelper;
use Mosparo\Kernel;
use Mosparo\Util\TokenGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'mosparo:finalize-update')]
class FinalizeUpdateCommand extends Command
{
    protected EntityManagerInterface $entityManager;

    protected ConfigHelper $configHelper;

    protected DesignHelper $designHelper;

    protected V14OptimizeRulesHelper $v14OptimizeRulesHelper;

    protected OptimizeRuleItemsAfterUpdateHelper $optimizeRuleItemsAfterUpdateHelper;

    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigHelper $configHelper,
        DesignHelper $designHelper,
        V14OptimizeRulesHelper $v14OptimizeRulesHelper,
        OptimizeRuleItemsAfterUpdateHelper $optimizeRuleItemsAfterUpdateHelper
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->configHelper = $configHelper;
        $this->designHelper = $designHelper;
        $this->v14OptimizeRulesHelper = $v14OptimizeRulesHelper;
        $this->optimizeRuleItemsAfterUpdateHelper = $optimizeRuleItemsAfterUpdateHelper;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Finalizes the update of the mosparo installation.')
            ->setHelp('After the files of mosparo were replaced, the update has to be finalized: the database migrations are executed, the cache is cleared, the frontend resources are refreshed and the rules and rule items are prepared for the new version. The administration interface does that when it is opened for the first time after an update. This command does the same without the administration interface, which an automated deployment cannot open.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Finalizes the update although the installed version is already the current version.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // An installation which was not completed has no database tables and no projects, so there is nothing
        // to finalize. The setup assistant executes the migrations and prepares the frontend resources itself.
        // That is not an error: the command is executed when mosparo is started, which includes the start
        // before the setup assistant is opened for the first time.
        if (!$this->configHelper->getEnvironmentConfigValue('mosparo_installed', false)) {
            $io->warning('mosparo is not installed yet, so there is no update to finalize. Please open mosparo in the browser to install it.');

            return Command::SUCCESS;
        }

        $installedVersion = $this->configHelper->getEnvironmentConfigValue('mosparo_installed_version');
        $updateAvailable = ($installedVersion !== Kernel::VERSION);
        $finalizationStarted = (bool) $this->configHelper->getEnvironmentConfigValue('mosparo_finalize_update');

        if (!$updateAvailable && !$finalizationStarted && !$input->getOption('force')) {
            $io->success(sprintf('The update to the version %s was finalized already.', Kernel::VERSION));

            return Command::SUCCESS;
        }

        $io->title(sprintf('Finalize the update to the version %s', Kernel::VERSION));

        // The frontend resources are built from the assets, so check them before anything is changed. Without
        // the assets the installation has no frontend at all and the update should not be finalized.
        $baseCssFilePath = $this->getBaseCssFilePath();
        if (!$baseCssFilePath || !file_exists($baseCssFilePath)) {
            $io->error('The assets of the frontend were not found, so the frontend resources cannot be built. Please build the assets before finalizing the update.');

            return Command::FAILURE;
        }

        if ($updateAvailable || $input->getOption('force')) {
            $io->section('Database migrations');

            if ($this->runCommand('doctrine:migrations:migrate', $output) !== Command::SUCCESS) {
                $io->error('Executing the database migrations failed. The configuration file still contains the previously installed version, so the finalization can be executed again after the problem was solved.');

                return Command::FAILURE;
            }

            // Close the connection to start with a clean state.
            $this->entityManager->getConnection()->close();

            // The version is stored only after the migrations were executed successfully. The flag marks the
            // finalization as started, so the administration interface processes the remaining tasks if this
            // command does not reach the end.
            if ($updateAvailable) {
                $tokenGenerator = new TokenGenerator();

                $this->configHelper->writeEnvironmentConfig([
                    'mosparo_installed_version' => Kernel::VERSION,
                    'mosparo_assets_version' => $tokenGenerator->generateShortToken(),
                    'mosparo_finalize_update' => true,
                ]);

                $finalizationStarted = true;
            }

            $io->section('Cache');

            if ($this->runCommand('cache:clear', $output) !== Command::SUCCESS) {
                $io->warning('Clearing the cache failed. Please clear the cache manually.');
            }
        }

        $io->section('Frontend resources');

        $numberOfProjects = count($this->entityManager->getRepository(Project::class)->findAll());

        $this->designHelper->refreshFrontendResourcesForAllProjects();

        $io->text(sprintf('Refreshed the frontend resources of %d project(s).', $numberOfProjects));

        $this->processOpenTasks($io, $this->v14OptimizeRulesHelper, 'Rules of the version 1.4');
        $this->processOpenTasks($io, $this->optimizeRuleItemsAfterUpdateHelper, 'Rule items');

        if ($finalizationStarted) {
            $this->configHelper->writeEnvironmentConfig([
                'mosparo_finalize_update' => false,
            ]);
        }

        $io->success(sprintf('The update to the version %s was finalized.', Kernel::VERSION));

        return Command::SUCCESS;
    }

    /**
     * Processes the open tasks of one of the migration helpers. Every call processes the tasks of a few
     * seconds only, the same way the administration interface calls the helper repeatedly, so this method
     * calls it until there are no tasks left.
     */
    protected function processOpenTasks(SymfonyStyle $io, V14OptimizeRulesHelper|OptimizeRuleItemsAfterUpdateHelper $helper, string $title): void
    {
        $numberOfTasks = $helper->countOpenTasks();
        if ($numberOfTasks === 0) {
            return;
        }

        $io->section($title);

        $progressBar = $io->createProgressBar($numberOfTasks);
        $progressBar->start();

        while (($numberOfProcessedTasks = $helper->processOpenTasks()) > 0) {
            $progressBar->advance($numberOfProcessedTasks);
        }

        $progressBar->finish();

        $io->newLine(2);
    }

    protected function runCommand(string $name, OutputInterface $output): int
    {
        $input = new ArrayInput([]);
        $input->setInteractive(false);

        return $this->getApplication()->find($name)->run($input, $output);
    }

    protected function getBaseCssFilePath(): ?string
    {
        try {
            return $this->designHelper->getBaseCssFilePath();
        } catch (\Throwable $e) {
            return null;
        }
    }
}

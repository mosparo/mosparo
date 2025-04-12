<?php

namespace Mosparo\Command;

use Mosparo\Enum\CleanupExecutor;
use Mosparo\Helper\CleanupHelper;
use Mosparo\Helper\ProjectHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mosparo:cleanup-database')]
class CleanupDatabaseCommand extends Command
{
    protected CleanupHelper $cleanupHelper;

    protected ProjectHelper $projectHelper;

    public function __construct(CleanupHelper $cleanupHelper, ProjectHelper $projectHelper)
    {
        parent::__construct();

        $this->cleanupHelper = $cleanupHelper;
        $this->projectHelper = $projectHelper;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Cleanups the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Disable the project related filter
        $this->projectHelper->unsetActiveProject();

        // Execute the cleanup process
        $this->cleanupHelper->cleanup(1000000, true, false, 0, CleanupExecutor::CLEANUP_COMMAND);

        return Command::SUCCESS;
    }
}
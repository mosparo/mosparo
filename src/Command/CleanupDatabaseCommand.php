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
            ->setDescription('Cleanup the database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Disable the project related filter
        $this->projectHelper->unsetActiveProject();

        // Sleep for 0.01 - 0.5 seconds before starting the cleanup process. This is done to reduce
        // the technical possibility that the cleanup process is executed multiple times at the
        // exact same moment (especially in a multi-node setup).
        usleep(mt_rand(10000, 500000));

        // Execute the cleanup process
        $this->cleanupHelper->cleanup(
            1000000,
            true,
            false,
            0,
            CleanupExecutor::CLEANUP_COMMAND
        );

        return Command::SUCCESS;
    }
}
<?php

namespace Mosparo\Command;

use Mosparo\Helper\CleanupHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mosparo:cleanup-database')]
class CleanupDatabaseCommand extends Command
{
    protected CleanupHelper $cleanupHelper;

    public function __construct(CleanupHelper $cleanupHelper)
    {
        parent::__construct();

        $this->cleanupHelper = $cleanupHelper;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Cleanups the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cleanupHelper->cleanup(1000000, true, false);

        return Command::SUCCESS;
    }
}
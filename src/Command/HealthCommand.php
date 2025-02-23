<?php

namespace Mosparo\Command;

use Mosparo\Helper\HealthHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'mosparo:health')]
class HealthCommand extends Command
{
    protected HealthHelper $healthHelper;

    public function __construct(HealthHelper $healthHelper)
    {
        parent::__construct();

        $this->healthHelper = $healthHelper;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Checks the health of the mosparo installation.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Health check');

        $result = $this->healthHelper->checkHealth();

        if ($result['statusCode'] !== 200) {
            $io->error('An error occurred when checking the health. You can find the details below.');

            $io->text("<options=underscore>Database status:</>\t " . $result['databaseStatus']);
            $io->text("<options=underscore>Error:</>\t\t\t " . $result['error']);
            $io->text("<options=underscore>Status code:</>\t\t " . $result['statusCode']);

            return Command::FAILURE;
        }

        $ignoredStatus = ['bypassed', 'test-skipped__not-configured', 'test-skipped__update-not-finished'];
        $addition = '';
        if (in_array($result['databaseStatus'], $ignoredStatus)) {
            $addition = sprintf(' (Detected status: %s)', $result['databaseStatus']);
        }


        $io->success('Everything okay' . $addition);

        return Command::SUCCESS;
    }
}
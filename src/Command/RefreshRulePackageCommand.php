<?php

namespace Mosparo\Command;

use Mosparo\Helper\RulePackageHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mosparo:rule-package:refresh', aliases: ['mosparo:rulesets:refresh'])]
class RefreshRulePackageCommand extends Command
{
    protected RulePackageHelper $rulePackageHelper;

    public function __construct(RulePackageHelper $rulePackageHelper)
    {
        parent::__construct();

        $this->rulePackageHelper = $rulePackageHelper;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Refreshes all rule packages.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->rulePackageHelper->fetchAll();

        return Command::SUCCESS;
    }
}
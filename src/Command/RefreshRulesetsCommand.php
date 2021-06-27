<?php

namespace Mosparo\Command;

use Mosparo\Helper\RulesetHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshRulesetsCommand extends Command
{
    protected static $defaultName = 'mosparo:rulesets:refresh';

    protected $rulesetHelper;

    public function __construct(string $name = null, RulesetHelper $rulesetHelper)
    {
        parent::__construct($name);

        $this->rulesetHelper = $rulesetHelper;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Refreshes all rulesets.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->rulesetHelper->downloadAll();

        if ($result === true) {
            return 0;
        }

        $output->write($result);

        return 1;
    }
}
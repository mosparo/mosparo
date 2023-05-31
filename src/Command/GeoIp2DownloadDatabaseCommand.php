<?php

namespace Mosparo\Command;

use Mosparo\Helper\GeoIp2Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeoIp2DownloadDatabaseCommand extends Command
{
    protected static $defaultName = 'mosparo:geoip2:download-database';

    protected GeoIp2Helper $geoIp2Helper;

    public function __construct(GeoIp2Helper $geoIp2Helper)
    {
        parent::__construct();

        $this->geoIp2Helper = $geoIp2Helper;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Downloads the MindMax GeoIP2 database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->geoIp2Helper->downloadDatabase();

        if ($result === true) {
            return Command::SUCCESS;
        }

        $output->write($result);

        return Command::FAILURE;
    }
}
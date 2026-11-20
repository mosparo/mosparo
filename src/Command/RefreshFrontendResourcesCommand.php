<?php

namespace Mosparo\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Helper\DesignHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mosparo:refresh-frontend-resources')]
class RefreshFrontendResourcesCommand extends Command
{
    protected EntityManagerInterface $entityManager;

    protected DesignHelper $designHelper;

    public function __construct(EntityManagerInterface $entityManager, DesignHelper $designHelper)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->designHelper = $designHelper;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Refreshes the frontend resources of all projects.')
            ->setHelp('The frontend resources contain the project designs and are built from the assets of the installed version, so they must be refreshed after an update. The update in the administration interface does that on its own. An installation that is updated by replacing the files, for example by starting a new container image, never runs that update, and this command refreshes the resources instead.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $formatter = $this->getHelper('formatter');

        try {
            $baseCssFilePath = $this->designHelper->getBaseCssFilePath();
        } catch (\InvalidArgumentException) {
            $baseCssFilePath = null;
        }

        if (!$baseCssFilePath || !file_exists($baseCssFilePath)) {
            $output->writeln($formatter->formatBlock(['The assets of the frontend were not found, so the frontend resources cannot be built. Please build the assets before refreshing the frontend resources.'], 'error', true));

            return Command::FAILURE;
        }

        $numberOfProjects = count($this->entityManager->getRepository(Project::class)->findAll());

        $this->designHelper->refreshFrontendResourcesForAllProjects();

        $output->writeln($formatter->formatBlock([sprintf('Refreshed the frontend resources of %d project(s).', $numberOfProjects)], 'info', true));

        return Command::SUCCESS;
    }
}

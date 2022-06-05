<?php

namespace Mosparo\Controller;

use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\SetupHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/upgrade")
 */
class UpgradeController extends AbstractController
{
    protected $kernel;

    protected $setupHelper;

    protected $configHelper;

    protected $mosparoVersion;

    public function __construct(KernelInterface $kernel, SetupHelper $setupHelper, ConfigHelper $configHelper, string $mosparoVersion)
    {
        $this->kernel = $kernel;
        $this->setupHelper = $setupHelper;
        $this->configHelper = $configHelper;
        $this->mosparoVersion = $mosparoVersion;
    }

    /**
     * @Route("/", name="upgrade_execute")
     */
    public function execute(): Response
    {
        // Prepare database and execute the migrations
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => 'doctrine:migrations:migrate',
            '-n'
        ));

        $output = new BufferedOutput();
        $application->run($input, $output);
        $output->fetch();

        // Update the installed version
        $this->configHelper->writeEnvironmentConfig([
            'mosparo_installed_version' => $this->mosparoVersion,
        ]);

        // Clear the cache after the upgrade
        $input = new ArrayInput(array(
            'command' => 'cache:clear',
            '-n'
        ));

        $output = new BufferedOutput();
        $application->run($input, $output);
        $output->fetch();

        return $this->render('upgrade/executed.html.twig');
    }
}
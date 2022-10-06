<?php

namespace Mosparo\Controller\Administration;

use Mosparo\Helper\SetupHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/administration/system")
 */
class SystemController extends AbstractController
{
    protected SetupHelper $setupHelper;

    protected Filesystem $fileSystem;

    protected string $mosparoVersion;

    protected string $projectDirectory;

    public function __construct(SetupHelper $setupHelper, Filesystem $fileSystem, string $mosparoVersion, string $projectDirectory)
    {
        $this->setupHelper = $setupHelper;
        $this->fileSystem = $fileSystem;
        $this->mosparoVersion = $mosparoVersion;
        $this->projectDirectory = $projectDirectory;
    }

    /**
     * @Route("/", name="administration_system")
     */
    public function settings(Request $request): Response
    {
        return $this->render('administration/system/system.html.twig', [
            'mosparoVersion' => $this->mosparoVersion,
            'serverInfo' => php_uname(),
            'phpVersion' => phpversion(),
            'extensionsData' => $this->setupHelper->getExtensionsData(),
            'licenseContent' => $this->getLicenseContent(),
        ]);
    }

    protected function getLicenseContent(): string
    {
        $filePath = $this->projectDirectory . '/LICENSE';
        if (!$this->fileSystem->exists($filePath)) {
            return 'LICENSE file not found. <br>See https://github.com/mosparo/mosparo/blob/master/LICENSE.';
        }

        $content = file_get_contents($filePath);
        $content = str_replace(PHP_EOL . PHP_EOL, '<br><br>', $content);
        $content = str_replace(PHP_EOL, ' ', $content);

        return $content;
    }
}

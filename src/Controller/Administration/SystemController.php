<?php

namespace Mosparo\Controller\Administration;

use Mosparo\Helper\ConnectionHelper;
use Mosparo\Helper\SetupHelper;
use Mosparo\Kernel;
use Mosparo\Util\PathUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administration/system')]
class SystemController extends AbstractController
{
    protected SetupHelper $setupHelper;

    protected ConnectionHelper $connectionHelper;

    protected Filesystem $fileSystem;

    protected string $projectDirectory;

    public function __construct(SetupHelper $setupHelper, ConnectionHelper $connectionHelper, Filesystem $fileSystem, string $projectDirectory)
    {
        $this->setupHelper = $setupHelper;
        $this->connectionHelper = $connectionHelper;
        $this->fileSystem = $fileSystem;
        $this->projectDirectory = $projectDirectory;
    }

    #[Route('/', name: 'administration_system')]
    public function systemOverview(Request $request): Response
    {
        return $this->render('administration/system/system.html.twig', [
            'mosparoVersion' => Kernel::VERSION,
            'serverInfo' => php_uname(),
            'phpVersion' => phpversion(),
            'extensionsData' => $this->setupHelper->getExtensionsData(),
            'downloadCheck' => $this->connectionHelper->checkIfDownloadIsPossible(),
            'licenseContent' => $this->getLicenseContent(),
        ]);
    }

    protected function getLicenseContent(): string
    {
        $filePath = PathUtil::prepareFilePath($this->projectDirectory . '/LICENSE');
        if (!$this->fileSystem->exists($filePath)) {
            return 'LICENSE file not found. <br>See https://github.com/mosparo/mosparo/blob/master/LICENSE.';
        }

        $content = file_get_contents($filePath);
        $content = str_replace(PHP_EOL . PHP_EOL, '<br><br>', $content);
        $content = str_replace(PHP_EOL, ' ', $content);

        return $content;
    }
}

<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Kernel;

class HealthHelper
{
    protected EntityManagerInterface $entityManager;

    protected bool $bypassHealthcheck;

    protected bool $mosparoInstalled;

    protected string $installedVersion;

    public function __construct(EntityManagerInterface $entityManager, bool $bypassHealthcheck, bool $mosparoInstalled, string $installedVersion)
    {
        $this->entityManager = $entityManager;
        $this->bypassHealthcheck = $bypassHealthcheck;
        $this->mosparoInstalled = $mosparoInstalled;
        $this->installedVersion = $installedVersion;
    }

    public function checkHealth(): array
    {
        if ($this->bypassHealthcheck) {
            return [
                'service' => 'mosparo',
                'healthy' => null,
                'databaseStatus' => 'bypassed',
                'error' => '',
                'statusCode' => 200,
            ];
        }

        $status = 200;
        $healthy = true;
        $error = null;
        $databaseStatus = 'unknown';
        if ($this->mosparoInstalled && $this->installedVersion === Kernel::VERSION) {
            try {
                $projectRepository = $this->entityManager->getRepository(Project::class);
                // Query the projects. If something does not work correctly when
                // connecting to the database, this will result in an exception.
                $projectRepository->findBy([], ['id' => 'ASC'], 1, 0);

                $databaseStatus = 'connected';
            } catch (\Exception $e) {
                $status = 500;
                $healthy = false;
                $error = $e->getMessage();

                $databaseStatus = 'connection-failed';
            }
        } else {
            if (!$this->mosparoInstalled) {
                $databaseStatus = 'test-skipped__not-configured';
            } else if ($this->installedVersion !== Kernel::VERSION) {
                $databaseStatus = 'test-skipped__update-not-finished';
            }
        }

        return [
            'service' => 'mosparo',
            'healthy' => $healthy,
            'databaseStatus' => $databaseStatus,
            'error' => $error,
            'statusCode' => $status,
        ];
    }
}
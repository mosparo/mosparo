<?php

namespace Mosparo\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    protected bool $mosparoInstalled;

    public function __construct(?bool $mosparoInstalled)
    {
        $this->mosparoInstalled = (bool) $mosparoInstalled;
    }

    #[Route('/health', name: 'mosparo_health', stateless: true, condition: 'ip_on_allow_list_routing(request.getClientIp(), env("MOSPARO_HEALTH_ALLOW_LIST"))')]
    public function healthAction(EntityManagerInterface $entityManager)
    {
        $status = 200;
        $healthy = true;
        $error = null;
        if ($this->mosparoInstalled) {
            try {
                $projectRepository = $entityManager->getRepository(Project::class);
                // Query the projects. If something does not work correctly when
                // connecting to the database, this will result in an exception.
                $projectRepository->findAll();

                $databaseStatus = 'connected';
            } catch (\Exception $e) {
                $status = 500;
                $healthy = false;
                $error = $e->getMessage();

                $databaseStatus = 'connection-failed';
            }
        } else {
            $databaseStatus = 'not-configured';
        }

        return new JsonResponse(array(
            'service' => 'mosparo',
            'healthy' => $healthy,
            'databaseStatus' => $databaseStatus,
            'error' => $error,
        ), $status);
    }
}

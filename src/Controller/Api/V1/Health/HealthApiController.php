<?php

namespace Mosparo\Controller\Api\V1\Health;

use Mosparo\Helper\HealthHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/health')]
class HealthApiController extends AbstractController
{
    #[Route('/check', name: 'health_api_check', methods: ['GET'], condition: 'ip_on_allow_list_routing(request.getClientIp(), env("MOSPARO_HEALTH_ALLOW_LIST"))', stateless: true)]
    public function healthAction(HealthHelper $healthHelper)
    {
        $result = $healthHelper->checkHealth();

        return new JsonResponse($result, $result['statusCode']);
    }
}

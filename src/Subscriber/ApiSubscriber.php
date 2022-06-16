<?php /** @noinspection ALL */

namespace Mosparo\Subscriber;

use Mosparo\Helper\ProjectHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ApiSubscriber implements EventSubscriberInterface
{
    protected ProjectHelper $projectHelper;

    public function __construct(ProjectHelper $projectHelper)
    {
        $this->projectHelper = $projectHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => [['onKernelRequest', -10]],
            ResponseEvent::class => [['onKernelResponse', -10]],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        // If the route is not a frontend api route, we do nothing
        if (!$request->headers->has('Origin') || strpos($request->get('_route'), 'frontend_api_') !== 0) {
            return;
        }

        $activeProject = $this->projectHelper->getActiveProject();
        if ($activeProject === null) {
            return;
        }

        $hostAllowed = false;
        $originHost = $this->removeProtocol($request->headers->get('Origin'));
        $hosts = $activeProject->getHosts();
        foreach ($hosts as $host) {
            if ($host === $originHost) {
                $hostAllowed = true;
                break;
            }
        }

        if (!$hostAllowed) {
            return;
        }

        $request = $event->getRequest();
        $method = $request->getRealMethod();
        if ($method === 'OPTIONS') {
            $response = new Response();
            $event->setResponse($response);
        }
    }

    protected function removeProtocol($origin)
    {
        if (strpos($origin, 'http://') === 0) {
            return substr($origin, 8);
        }

        if (strpos($origin, 'https://') === 0) {
            return substr($origin, 9);
        }

        return $origin;
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        // Don't do anything if it's not the master request.
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        // If the route is not a frontend api route, we do nothing
        if (strpos($request->get('_route'), 'frontend_api_') !== 0) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
        $response->headers->set('Access-Control-Allow-Methods', 'POST');
    }
}
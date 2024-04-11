<?php /** @noinspection ALL */

namespace Mosparo\Subscriber;

use Mosparo\Entity\Project;
use Mosparo\Helper\ProjectHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
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
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // If the route is not a frontend api route, we do nothing
        if (!$request->headers->has('Origin') || strpos($request->attributes->get('_route'), 'frontend_api_') !== 0) {
            return;
        }

        $activeProject = $this->projectHelper->getActiveProject();
        if ($activeProject === null) {
            return;
        }

        if (!$this->isOriginAllowed($request, $activeProject)) {
            return;
        }

        $request = $event->getRequest();
        $method = $request->getRealMethod();
        if ($method === 'OPTIONS') {
            $response = new Response();
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        // Don't do anything if it's not the master request.
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // If the route is not a frontend api route, we do nothing
        if (strpos($request->attributes->get('_route'), 'frontend_api_') !== 0) {
            return;
        }

        $activeProject = $this->projectHelper->getActiveProject();
        if ($activeProject === null) {
            return;
        }

        if ($this->isOriginAllowed($request, $activeProject)) {
            $response = $event->getResponse();
            $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
            $response->headers->set('Access-Control-Allow-Methods', 'POST');
        }
    }

    protected function removeProtocol($origin)
    {
        if (strpos($origin, 'http://') === 0) {
            return substr($origin, 7);
        }

        if (strpos($origin, 'https://') === 0) {
            return substr($origin, 8);
        }

        return $origin;
    }

    protected function isOriginAllowed(Request $request, Project $project)
    {
        $originHost = $this->removeProtocol($request->headers->get('Origin'));

        foreach ($project->getHosts() as $host) {
            if ($host === $originHost) {
                return true;
            } else if (strpos($host, '*') === 0 && $this->matchingOrigin($host, $originHost)) {
                return true;
            }
        }

        return false;
    }

    protected function matchingOrigin(string $host, string $originHost)
    {
        // Global wildcard, this should not be used at all
        if ($host === '*') {
            return true;
        }

        // The star is always at the beginning, so remove it from the string
        $hostWithout = substr($host, 1);

        // If the host without star is at the end of the origin, we have a match
        if (str_ends_with($originHost, $hostWithout)) {
            return true;
        }

        // If the first character is a dot and the host is the same but without dot, the origin host is allowed because
        // *.example.com as host will also match the origin `example.com`
        if (substr($hostWithout, 0, 1) === '.' && substr($hostWithout, 1) === $originHost) {
            return true;
        }

        return false;
    }
}
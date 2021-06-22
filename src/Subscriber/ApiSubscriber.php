<?php

namespace Mosparo\Subscriber;

use http\Env\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ApiSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
            ResponseEvent::class => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        // If the route is not a frontend api route, we do nothing
        if (strpos($request->get('_route'), 'frontend_api_') !== 0) {
            return;
        }

        // @todo: check if the publicKey is set and if the project exists and if the origin domain does exist as project domain

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
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        // If the route is not a frontend api route, we do nothing
        if (strpos($request->get('_route'), 'frontend_api_') !== 0) {
            return;
        }

        // @todo: Replace the star with the origin domain to make it secure

        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST');
    }
}
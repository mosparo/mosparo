<?php

namespace Mosparo\Subscriber;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SetupSubscriber implements EventSubscriberInterface
{
    protected $container;

    protected $router;

    protected $installed;

    protected $allowedRoutes = [
        'setup_start',
        'setup_prerequisites',
        'setup_database',
        'setup_mail',
        'setup_other',
        'setup_install',
    ];

    public function __construct(ContainerInterface $container, UrlGeneratorInterface $router, $installed, $debug = false)
    {
        $this->container = $container;
        $this->router = $router;
        $this->installed = $installed;

        if ($debug) {
            $this->allowedRoutes = array_merge($this->allowedRoutes, [
                '_wdt',
                '_profiler',
                '_profiler_search',
                '_profiler_search_bar',
                '_profiler_search_results',
                '_profiler_router',
            ]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($this->installed) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->get('_route');
        if (in_array($route, $this->allowedRoutes)) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->router->generate('setup_start')));
    }
}
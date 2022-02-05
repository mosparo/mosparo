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

    public function __construct(ContainerInterface $container, UrlGeneratorInterface $router)
    {
        $this->container = $container;
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (strpos($request->getRequestUri(), '/setup') === 0) {
            return;
        }

        $isInstalled = ($this->container->hasParameter('MOSPARO_INSTALLED')) ? $this->container->getParameter('MOSPARO_INSTALLED') : false;
        if ($isInstalled) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->router->generate('setup_start')));
    }
}
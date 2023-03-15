<?php

namespace Mosparo\Subscriber;

use Mosparo\Helper\InterfaceHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

class InterfaceSubscriber implements EventSubscriberInterface
{
    protected Environment $twig;

    protected InterfaceHelper $interfaceHelper;

    public function __construct(Environment $twig, InterfaceHelper $interfaceHelper)
    {
        $this->twig = $twig;
        $this->interfaceHelper = $interfaceHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Do not set the interface options for stateless requests
        if ($event->getRequest()->attributes->get('_stateless', false)) {
            return;
        }

        $request = $event->getRequest();

        $this->twig->addGlobal('colorMode', $this->interfaceHelper->determineColorMode($request));
    }
}
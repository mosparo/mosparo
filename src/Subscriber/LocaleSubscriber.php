<?php

namespace Mosparo\Subscriber;

use Mosparo\Helper\LocaleHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;
use Twig\Extension\CoreExtension;

class LocaleSubscriber implements EventSubscriberInterface
{
    protected Environment $twig;

    protected LocaleHelper $localeHelper;

    public function __construct(Environment $twig, LocaleHelper $localeHelper)
    {
        $this->twig = $twig;
        $this->localeHelper = $localeHelper;
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

        // Do not set the locale for stateless requests
        if ($event->getRequest()->attributes->get('_stateless', false)) {
            return;
        }

        $request = $event->getRequest();
        $coreExtension = $this->twig->getExtension(CoreExtension::class);

        [$locale, $dateFormat, $timeFormat, $timezone] = $this->localeHelper->determineLocaleValues($request);

        if ($locale != '') {
            $request->setLocale($locale);
        }

        $dateTimeFormat = sprintf('%s, %s', $dateFormat, $timeFormat);

        $coreExtension->setDateFormat($dateTimeFormat);
        $coreExtension->setTimezone($timezone);
    }
}
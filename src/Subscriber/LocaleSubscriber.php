<?php

namespace Mosparo\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;
use Twig\Extension\CoreExtension;

class LocaleSubscriber implements EventSubscriberInterface
{
    protected Environment $twig;

    protected string $defaultDateFormat;

    protected string $defaultTimeFormat;

    protected string $defaultTimezone;

    public function __construct(Environment $twig, $defaultDateFormat, $defaultTimeFormat, $defaultTimezone)
    {
        $this->twig = $twig;
        $this->defaultDateFormat = $defaultDateFormat;
        $this->defaultTimeFormat = $defaultTimeFormat;
        $this->defaultTimezone = $defaultTimezone;
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

        $request = $event->getRequest();
        $coreExtension = $this->twig->getExtension(CoreExtension::class);

        $browserLocale = null;
        if (!empty($request->getPreferredLanguage())) {
            $browserLocale = $request->getPreferredLanguage();
        }

        $locale = '';
        $dateFormat = $this->defaultDateFormat;
        $timeFormat = $this->defaultTimeFormat;
        $timezone = $this->defaultTimezone;

        $session = $request->getSession();
        if ($session !== null && $session->has('userLocale')) {
            $userLocale = $session->get('userLocale');

            if ($userLocale === 'browser' && $browserLocale !== null) {
                $locale = $browserLocale;
            } else if ($userLocale !== null && $userLocale !== 'default') {
                $locale = $userLocale;
            }

            $userDateFormat = $session->get('userDateFormat');
            if ($userDateFormat !== null && $userDateFormat !== 'default') {
                $dateFormat = $userDateFormat;
            }

            $userTimeFormat = $session->get('userTimeFormat');
            if ($userTimeFormat !== null && $userTimeFormat !== 'default') {
                $timeFormat = $userTimeFormat;
            }

            $userTimezone = $session->get('userTimezone');
            if ($userTimezone !== null && $userTimezone !== 'default') {
                $timezone = $userTimezone;
            }
        } else if ($browserLocale !== null) {
            $locale = $browserLocale;
        }

        if ($locale != '') {
            $request->setLocale($locale);
        }

        $dateTimeFormat = sprintf('%s, %s', $dateFormat, $timeFormat);

        $coreExtension->setDateFormat($dateTimeFormat);
        $coreExtension->setTimezone($timezone);
    }
}
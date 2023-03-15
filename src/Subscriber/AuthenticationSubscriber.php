<?php

namespace Mosparo\Subscriber;

use Mosparo\Entity\User;
use Mosparo\Helper\InterfaceHelper;
use Mosparo\Helper\LocaleHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class AuthenticationSubscriber implements EventSubscriberInterface
{
    protected LocaleHelper $localeHelper;

    protected InterfaceHelper $interfaceHelper;

    public function __construct(LocaleHelper $localeHelper, InterfaceHelper $interfaceHelper)
    {
        $this->localeHelper = $localeHelper;
        $this->interfaceHelper = $interfaceHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => [['onLoginSuccess', 20]],
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event)
    {
        $request = $event->getRequest();

        if (!($event->getUser() instanceof User)) {
            return;
        }

        $this->localeHelper->storeUserSettingsInSession($request->getSession(), $event->getUser());
        $this->interfaceHelper->storeUserSettingsInSession($request->getSession(), $event->getUser());
    }
}
<?php

namespace Mosparo\Subscriber;

use Mosparo\Entity\User;
use Mosparo\Enum\AuthSource;
use Mosparo\Helper\ConfigHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserSubscriber implements EventSubscriberInterface
{
    protected Security $security;

    protected ConfigHelper $configHelper;

    protected UrlGeneratorInterface $router;

    protected array $allowed2faRoutes = [
        'security_reauthentication',
        'security_logout',
        'account_two_factor_status',
        'account_two_factor_start',
        'account_two_factor_verify',
        'account_two_factor_backup_codes',
    ];

    protected array $allowedPasskeyRoutes = [
        '2fa_login',
        '2fa_login_check',
        'security_reauthentication',
        'security_logout',
        'account_webauthn_list',
        'webauthn.controller.creation.request.add_passkey',
        'webauthn.controller.creation.response.add_passkey',
        'webauthn.controller.request.request.reauthenticate',
        'webauthn.controller.request.response.reauthenticate',
    ];

    public function __construct(Security $security, ConfigHelper $configHelper, UrlGeneratorInterface $router)
    {
        $this->security = $security;
        $this->configHelper = $configHelper;
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onKernelRequest', 5],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!($user instanceof User) || $user->getAuthSource() !== AuthSource::LOCAL || $user->getAuthMethod() !== 'password') {
            return;
        }

        $environmentConfig = $this->configHelper->readEnvironmentConfig();
        $enforce2fa = ($environmentConfig['authentication_enforce_two_factor_authentication'] ?? false);
        $enforcePasskey = ($environmentConfig['authentication_enforce_passkey_authentication'] ?? false);

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (!$user->isGoogleAuthenticatorEnabled() && $enforce2fa && !$enforcePasskey) {
            $request->attributes->set('userEnforce2fa', true);

            if (in_array($route, $this->allowed2faRoutes)) {
                return;
            }

            $event->setResponse(new RedirectResponse($this->router->generate('account_two_factor_start')));
        }

        if ($enforcePasskey) {
            $request->attributes->set('userEnforcePasskey', true);

            if (in_array($route, $this->allowedPasskeyRoutes)) {
                return;
            }

            $event->setResponse(new RedirectResponse($this->router->generate('account_webauthn_list')));
        }
    }
}

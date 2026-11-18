<?php

namespace Mosparo\Security;

use Mosparo\Entity\User;
use Mosparo\Enum\AuthSource;
use Mosparo\Security\Oidc\OidcManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    protected RequestStack $requestStack;

    protected OidcManager $oidcManager;

    public function __construct(RequestStack $requestStack, OidcManager $oidcManager)
    {
        $this->requestStack = $requestStack;
        $this->oidcManager = $oidcManager;
    }

	/**
	 * @inheritDoc
	 */
	public function checkPreAuth(UserInterface $user): void
	{
        if (!$user instanceof User) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $currentRoute = $request->attributes->get('_route');
        $allowedMethod = $user->getAuthMethod() ?? 'password';

        if ($user->getAuthSource() === AuthSource::LOCAL && in_array($currentRoute, ['security_login', '2fa_login_check'])) {
            if ($request->isMethod('POST') && ($request->request->has('_password') || $request->request->has('_auth_code'))) {
                if ($allowedMethod !== 'password') {
                    throw new BadCredentialsException('The user is not allowed to use password authentication.');
                }
                return;
            }
        }

        if ($user->getAuthSource() === AuthSource::REMOTE_OIDC && $currentRoute === 'login_oidc_check') {
            if ($request->query->has('code') && $request->query->has('state')) {
                if (str_starts_with($allowedMethod, 'oidc_')) {
                    $routeParams = $request->attributes->get('_route_params', []);
                    $key = $this->oidcManager->getClientKeyForAlias($routeParams['alias'] ?? '');
                    $authMethod = sprintf('oidc_%s', $key);

                    if ($allowedMethod !== $authMethod) {
                        throw new BadCredentialsException('User is not allowed to login with this client.');
                    }

                    return;
                }
            }
        }

        if ($user->getAuthSource() === AuthSource::LOCAL && $currentRoute === 'webauthn.controller.security.main.request.result') {
            if ($allowedMethod !== 'webauthn') {
                throw new BadCredentialsException('The user is not allowed to use webauthn authentication.');
            }
            return;
        }

        throw new BadCredentialsException('Invalid authentication method or not correctly handled by the allowed authentication method.');
	}

	/**
	 * @inheritDoc
	 */
	public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
	{
	}
}

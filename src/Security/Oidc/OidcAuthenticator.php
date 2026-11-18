<?php

namespace Mosparo\Security\Oidc;

use Mosparo\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class OidcAuthenticator implements InteractiveAuthenticatorInterface, AuthenticationEntryPointInterface
{
    protected OidcManager $oidcManager;

    protected RouterInterface $router;

    protected LoggerInterface $logger;

    protected array $clientAuthenticators = [];

    protected ?InteractiveAuthenticatorInterface $activeAuthenticator = null;

    public function __construct(OidcManager $oidcManager, RouterInterface $router, LoggerInterface $logger, array $clientAuthenticators)
    {
        $this->oidcManager = $oidcManager;
        $this->router = $router;
        $this->logger = $logger;
        $this->clientAuthenticators = $clientAuthenticators;
    }

	/**
	 * @inheritDoc
	 */
	public function supports(Request $request): ?bool
	{
        $alias = $request->attributes->get('_route_params')['alias'] ?? null;
        if ($alias === null) {
            return false;
        }

        $clientKey = $this->oidcManager->getClientKeyForAlias($alias);
        if ($clientKey === null) {
            return false;
        }

        $authenticator = $this->clientAuthenticators[$clientKey] ?? null;
        if (!$authenticator) {
            return false;
        }

        $this->activeAuthenticator = $authenticator;

        return $request->attributes->get('_route') === 'login_oidc_check';
	}

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('security_login'));
    }

	/**
	 * @inheritDoc
	 */
	public function authenticate(Request $request): Passport
	{
        if (!$this->activeAuthenticator) {
            throw new Exception('Invalid call.');
        }

        if (!$request->query->has('code') || !$request->query->has('state')) {
            $routeParams = $request->attributes->get('_route_params', []);
            $this->logger->info('The request had no code or state parameter.', [
                'clientAlias' => $routeParams['alias'] ?? null,
                'error' => $request->query->get('error'),
                'errorDescription' => $request->query->get('error_description'),
            ]);

            throw new CustomUserMessageAuthenticationException('security.oidc.errorMessages.oidcResponseWithoutCodeOrState');
        }

		return $this->activeAuthenticator->authenticate($request);
	}

	/**
	 * @inheritDoc
	 */
	public function createToken(Passport $passport, string $firewallName): TokenInterface
	{
        if (!$this->activeAuthenticator) {
            throw new Exception('Invalid call.');
        }

        return $this->activeAuthenticator->createToken($passport, $firewallName);
	}

	/**
	 * @inheritDoc
	 */
	public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
	{
        if (!$this->activeAuthenticator) {
            throw new Exception('Invalid call.');
        }

        return $this->activeAuthenticator->onAuthenticationSuccess($request, $token, $firewallName);
	}

	/**
	 * @inheritDoc
	 */
	public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
	{
        if (!$this->activeAuthenticator) {
            throw new Exception('Invalid call.');
        }

        return $this->activeAuthenticator->onAuthenticationFailure($request, $exception);
	}

	/**
	 * @inheritDoc
	 */
	public function isInteractive(): bool
	{
		return true;
	}
}

<?php

namespace Mosparo\Controller;

use DateTime;
use Drenso\OidcBundle\OidcClientLocator;
use LogicException;
use Mosparo\Entity\User;
use Mosparo\Enum\AuthSource;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Security\Oidc\OidcManager;
use Mosparo\Security\Voter\RecentlyAuthenticatedVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RateLimiter\PeekableRequestRateLimiterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'security_login', condition: 'ip_on_allow_list_routing(request.getClientIp(), env("backend_access_ip_allow_list"))')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, OidcManager $oidcManager, ConfigHelper $configHelper): Response
    {
        if (in_array('application/json', $request->getAcceptableContentTypes())) {
            return new JsonResponse(['unauthorized' => $this->isGranted('IS_AUTHENTICATED_FULLY')]);
        }

        $mosparoUrl = $configHelper->getEnvironmentConfigValue('mosparo_url');
        if ($request->getMethod() === 'GET' && $mosparoUrl) {
            $mainHostname = parse_url($mosparoUrl, PHP_URL_HOST);

            if (mb_strtolower($mainHostname) !== mb_strtolower($request->getHost())) {
                $newUrl = rtrim($mosparoUrl, '/') . $request->getPathInfo() . (($request->getQueryString()) ? '?' . $request->getQueryString() : '');
                return $this->redirect($newUrl);
            }
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'oidcClients' => $oidcManager->getEnabledClients(),
        ]);
    }

    #[Route('/login', name: 'security_login_unauthorized')]
    public function loginAccessDenied(): Response
    {
        /**
         * It's necessary to have these two /login routes. The problem is that the firewall will redirect the
         * user to the /login route, as long as the condition is not met. This will end in an endless loop, so
         * with this additional route, we can make sure that there is no endless loop if the backend access is
         * restricted.
         */
        throw new AccessDeniedHttpException('Access denied.');
    }

    #[Route('/oidc/login/{alias}', name: 'login_oidc')]
    public function oidcLogin(OidcManager $oidcManager, OidcClientLocator $clientLocator, string $alias): RedirectResponse
    {
        $clientKey = $oidcManager->getClientKeyForAlias($alias);
        if (!$clientKey) {
            throw $this->createNotFoundException();
        }

        $oidcClientConfig = $oidcManager->getClientConfiguration($clientKey);
        $oidcClient = $clientLocator->getClient($clientKey);

        // Redirect to authorization @ OIDC provider
        $response = $oidcClient->generateAuthorizationRedirect(scopes: array_merge(['openid', 'email', 'profile'], $oidcClientConfig->getAdditionalScopes() ?? []));

        return $response;
    }

    #[Route('/oidc/check/{alias}', name: 'login_oidc_check')]
    public function oidcLoginCheck(Request $request, OidcManager $oidcManager, OidcClientLocator $clientLocator, string $alias)
    {
        $clientKey = $oidcManager->getClientKeyForAlias($alias);
        if (!$clientKey) {
            throw $this->createNotFoundException();
        }

        throw new LogicException('This method can be blank - it will be intercepted by the mosparooidc key on the firewall.');
    }

    #[Route('/logout', name: 'security_logout')]
    public function logout()
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}

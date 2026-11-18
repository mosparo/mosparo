<?php

namespace Mosparo\Security\Webauthn;

use DateTime;
use Mosparo\Entity\User;
use Mosparo\Exception;
use Mosparo\Security\Voter\RecentlyAuthenticatedVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Webauthn\Bundle\Security\Handler\DefaultRequestOptionsHandler;
use Webauthn\Bundle\Security\Handler\RequestOptionsHandler;
use Webauthn\Bundle\Security\Handler\SuccessHandler;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialUserEntity;

class WebauthnHandler implements SuccessHandler, RequestOptionsHandler, AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    protected Security $security;

    protected RouterInterface $router;

    protected DefaultRequestOptionsHandler $defaultRequestOptionsHandler;

    public function __construct(Security $security, RouterInterface $router, DefaultRequestOptionsHandler $defaultRequestOptionsHandler)
    {
        $this->security = $security;
        $this->router = $router;
        $this->defaultRequestOptionsHandler = $defaultRequestOptionsHandler;
    }

    public function onRequestOptions(
        PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions,
        ?PublicKeyCredentialUserEntity $userEntity,
        ?Request $request = null
    ): Response {
        $currentUser = $this->security->getUser();
        if (!($currentUser instanceof User) || $currentUser->getAuthMethod() !== 'webauthn' || $currentUser->getUserHandle() === null || $userEntity === null) {
            throw new Exception('User not logged in.');
        }

        if (!hash_equals($currentUser->getUserHandle(), $userEntity->id)) {
            throw new Exception('Wrong user requested.');
        }

        return $this->defaultRequestOptionsHandler->onRequestOptions($publicKeyCredentialRequestOptions, $userEntity, $request);
    }

    public function onSuccess(
        Request $request,
        ?PublicKeyCredential $publicKeyCredential = null,
        ?PublicKeyCredentialOptions $publicKeyCredentialOptions = null,
        ?PublicKeyCredentialUserEntity $userEntity = null
    ): Response {
        $request->getSession()->set(RecentlyAuthenticatedVoter::SESSION_KEY, new DateTime());

        // Default route is account_overview, since this onSuccess will be called by the reauthentication process
        // which is triggered by an account functionality.
        return $this->createRedirectResponse($request, 'account_overview');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        // Default route is project_list_root, since this onAuthenticationSuccess will be called by the normal
        // login process, so the default route is the normal first page after login.
        return $this->createRedirectResponse($request, 'project_list_root');
    }

    protected function createRedirectResponse(Request $request, string $defaultRoute): JsonResponse
    {
        $targetPath = $this->getTargetPath($request->getSession(), 'main');
        if ($targetPath) {
            $this->removeTargetPath($request->getSession(), 'main');
        } else {
            $targetPath = $this->router->generate($defaultRoute);
        }

        return new JsonResponse([
            'status' => 'ok',
            'errorMessage' => '',
            'redirectUrl' => $targetPath,
        ]);
    }
}

<?php

namespace Mosparo\Security;

use Mosparo\Entity\User;
use Mosparo\Enum\AuthSource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    use TargetPathTrait;

    protected Security $security;

    protected RouterInterface $router;

    public function __construct(Security $security, RouterInterface $router)
    {
        $this->security = $security;
        $this->router = $router;
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        if (!($this->security->getUser() instanceof User) || $this->security->getUser()->getAuthSource() !== AuthSource::LOCAL) {
            return null;
        }

        if (!$this->security->isGranted('RECENTLY_AUTHENTICATED')) {
            $route = $request->attributes->get('_route');

            if ($request->getMethod() === 'GET') {
                $this->saveTargetPath($request->getSession(), 'main', $this->router->generate($route, $request->attributes->get('_route_params')));
            }

            if (in_array($route, ['webauthn.controller.creation.request.add_passkey', 'webauthn.controller.creation.response.add_passkey'])) {
                return new JsonResponse(['error' => true, 'errorMessage' => 'Not recently authorized', 'authenticationRequired' => true], 401);
            } else {
                return new RedirectResponse($this->router->generate('security_reauthentication'));
            }
        }

        return null;
    }
}

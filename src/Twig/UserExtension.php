<?php

namespace Mosparo\Twig;

use Mosparo\Entity\User;
use Mosparo\Enum\AuthSource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class UserExtension extends AbstractExtension implements GlobalsInterface
{
    protected Security $security;

    protected RequestStack $requestStack;

    public function __construct(Security $security, RequestStack $requestStack)
    {
        $this->security = $security;
        $this->requestStack = $requestStack;
    }

    public function getGlobals(): array
    {
        $vars = [
            'userEnforce2fa' => false,
            'userEnforcePasskey' => false,
            'hideFunctions' => false,
        ];

        $user = $this->security->getUser();
        if (!($user instanceof User) || $user->getAuthSource() !== AuthSource::LOCAL || $user->getAuthMethod() !== 'password') {
            return $vars;
        }

        $request = $this->requestStack->getMainRequest();

        if ($request->attributes->get('userEnforce2fa')) {
            $vars = array_merge($vars, [
                'userEnforce2fa' => true,
                'hideFunctions' => true,
            ]);
        }

        if ($request->attributes->get('userEnforcePasskey')) {
            $vars = array_merge($vars, [
                'userEnforcePasskey' => true,
                'hideFunctions' => true,
            ]);
        }

        return $vars;
    }
}

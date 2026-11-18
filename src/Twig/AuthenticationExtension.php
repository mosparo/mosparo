<?php

namespace Mosparo\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Enum\AuthSource;
use Mosparo\Security\Oidc\Configuration\OidcClientConfigurationInterface;
use Mosparo\Security\Oidc\OidcManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AuthenticationExtension extends AbstractExtension
{
    protected EntityManagerInterface $entityManager;

    protected OidcManager $oidcManager;

    public function __construct(EntityManagerInterface $entityManager, OidcManager $oidcManager)
    {
        $this->entityManager = $entityManager;
        $this->oidcManager = $oidcManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_oidc_client_for_auth_method', [$this, 'getOidcClientForAuthMethod']),
            new TwigFunction('count_users_for_oidc_client', [$this, 'countUsersForOidcClient'])
        ];
    }

    public function getOidcClientForAuthMethod(string $authMethod): ?OidcClientConfigurationInterface
    {
        return $this->oidcManager->getOidcClientForAuthMethod($authMethod);
    }

    public function countUsersForOidcClient(string $key)
    {
        return $this->oidcManager->countUsersForOidcClient($key);
    }
}

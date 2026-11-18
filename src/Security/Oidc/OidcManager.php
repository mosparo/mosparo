<?php

namespace Mosparo\Security\Oidc;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Enum\AuthSource;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Security\Oidc\Configuration\OidcClientConfigurationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Routing\RouterInterface;

class OidcManager
{
    protected EntityManagerInterface $entityManager;

    protected ConfigHelper $configHelper;

    protected RouterInterface $router;

    protected iterable $clients;

    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigHelper $configHelper,
        RouterInterface $router,
        #[AutowireIterator('mosparo.oidc_client_config')] iterable $clients
    ) {
        $this->entityManager = $entityManager;
        $this->configHelper = $configHelper;
        $this->router = $router;
        $this->clients = $clients;
    }

    public static function getClientKeys(): array
    {
        return ['oidc0', 'oidc1', 'oidc2', 'oidc3', 'oidc4', 'oidc5', 'oidc6', 'oidc7', 'oidc8', 'oidc9'];
    }

    public function getClients(): array
    {
        $configuredClients = [];
        foreach ($this->clients as $client) {
            if ($client->isConfigured()) {
                $configuredClients[$client->getKey()] = $client;
            }
        }

        return $configuredClients;
    }

    public function getEnabledClients(): array
    {
        $enabledClients = [];
        foreach ($this->clients as $client) {
            if ($client->isEnabled()) {
                $enabledClients[$client->getKey()] = $client;
            }
        }

        return $enabledClients;
    }

    public function getClientKeyForAlias(string $alias): ?string
    {
        foreach ($this->clients as $client) {
            if (!$client->isEnabled()) {
                continue;
            }

            if ($client->getAlias() === $alias) {
                return $client->getKey();
            }
        }

        return null;
    }

    public function getClientConfiguration(string $key): ?OidcClientConfigurationInterface
    {
        foreach ($this->clients as $client) {
            if ($client->getKey() === $key) {
                return $client;
            }
        }

        return null;
    }

    public function getOidcClientForAuthMethod(string $authMethod, bool $isAlias = false): ?OidcClientConfigurationInterface
    {
        $key = substr($authMethod, strpos($authMethod, '_') + 1);

        if ($isAlias) {
            $key = $this->getClientKeyForAlias($key);
            if (!$key) {
                return null;
            }
        }

        return $this->getClientConfiguration($key);
    }

    public function hasEnabledClients(): bool
    {
        return !empty($this->getEnabledClients());
    }

    public function getAvailableKey(): ?string
    {
        foreach ($this->clients as $client) {
            if (!$client->isConfigured()) {
                return $client->getKey();
            }
        }

        return null;
    }

    public function saveClientConfiguration(OidcClientConfigurationInterface $clientConfiguration): void
    {
        $fullConfig = $this->configHelper->getEnvironmentConfigValues();
        $prefixedValues = $this->buildEnvironmentConfigValues($clientConfiguration);

        foreach ($prefixedValues as $key => $value) {
            if (array_key_exists($key, $fullConfig) && $value === null) {
                unset($fullConfig[$key]);
                continue;
            }

            if ($value === null) {
                continue;
            }

            $fullConfig[$key] = $value;
        }

        $this->configHelper->writeEnvironmentConfig($fullConfig, false);
    }

    public function deleteClientConfiguration(OidcClientConfigurationInterface $clientConfiguration): void
    {
        $fullConfig = $this->configHelper->getEnvironmentConfigValues();
        $prefixedValues = $this->buildEnvironmentConfigValues($clientConfiguration);

        foreach (array_keys($prefixedValues) as $key) {
            if (!isset($fullConfig[$key])) {
                continue;
            }

            unset($fullConfig[$key]);
        }

        $this->configHelper->writeEnvironmentConfig($fullConfig, false);
    }

    protected function buildEnvironmentConfigValues(OidcClientConfigurationInterface $clientConfiguration): array
    {
        $additionalScopes = null;
        if (!empty($clientConfiguration->getAdditionalScopes(false) ?? null)) {
            $additionalScopes = json_encode(array_values($clientConfiguration->getAdditionalScopes(false)));
        }

        $values = [
            'enabled' => $clientConfiguration->isEnabled(),
            'name' => $clientConfiguration->getName(),
            'alias' => $clientConfiguration->getAlias(),
            'well_known_url' => $clientConfiguration->getWellKnownUrl(),
            'client_id' => $clientConfiguration->getClientId(),
            'client_secret' => $clientConfiguration->getClientSecret(),
            'redirect_route' => $this->router->generate('login_oidc_check', ['alias' => $clientConfiguration->getAlias()]),
            'icon_url' => $clientConfiguration->getIconUrl(),
            'icon_origin' => $this->parseOriginFromIconUrl($clientConfiguration->getIconUrl()),
            'assume_email_verified' => boolval($clientConfiguration->isAssumeEmailVerified(false)),
            'enable_retrieve_user_info' => boolval($clientConfiguration->isEnableRetrieveUserInfo(false)),
            'additional_scopes' => $additionalScopes,
        ];
        $prefix = $clientConfiguration->getKey() . '_';
        $prefixedValues = [];
        foreach ($values as $key => $value) {
            $prefixedValues[$prefix . $key] = $value;
        }

        return $prefixedValues;
    }

    public function countUsersForOidcClient(string $key)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(u.id) AS userCount')
            ->from('Mosparo\Entity\User', 'u')
            ->where('u.authSource = :authSource')
            ->andWhere('u.authMethod = :authMethod')
            ->setParameter(':authSource', AuthSource::REMOTE_OIDC)
            ->setParameter(':authMethod', 'oidc_' . $key)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    protected function parseOriginFromIconUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $parts = parse_url($url);
        $origin = sprintf('%s://%s', $parts['scheme'], $parts['host']);

        if (isset($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }
}

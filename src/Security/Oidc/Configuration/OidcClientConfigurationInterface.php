<?php

namespace Mosparo\Security\Oidc\Configuration;

interface OidcClientConfigurationInterface
{
    public function getKey(): string;
    public function isEnabled(): bool;
    public function getAlias(): ?string;
    public function getName(): ?string;
    public function getWellKnownUrl(): ?string;
    public function getClientId(): ?string;
    public function getClientSecret(): ?string;
    public function getRedirectRoute(): ?string;
    public function getIconUrl(): ?string;
    public function isAssumeEmailVerified(bool $returnDefaultValue = true): ?bool;
    public function isEnableRetrieveUserInfo(bool $returnDefaultValue = true): ?bool;
    public function getAdditionalScopes(bool $returnDefaultValue = true): ?array;
}

<?php

namespace Mosparo\Security\Oidc\Configuration;

class OidcClientConfiguration implements OidcClientConfigurationInterface
{
    protected string $key;

    protected bool $enabled = false;

    protected ?string $alias;

    protected ?string $name;

    protected ?string $wellKnownUrl;

    protected ?string $clientId;

    protected ?string $clientSecret;

    protected ?string $redirectRoute;

    protected ?string $iconUrl;

    protected ?bool $assumeEmailVerified;

    protected ?bool $enableRetrieveUserInfo;

    protected ?array $additionalScopes;

    public function __construct(
        string $key,
        bool $enabled,
        ?string $alias,
        ?string $name,
        ?string $wellKnownUrl,
        ?string $clientId,
        #[\SensitiveParameter] ?string $clientSecret,
        ?string $redirectRoute,
        ?string $iconUrl,
        ?bool $assumeEmailVerified,
        ?bool $enableRetrieveUserInfo,
        ?array $additionalScopes
    ) {
        $this->key = $key;
        $this->enabled = $enabled;
        $this->alias = $alias;
        $this->name = $name;
        $this->wellKnownUrl = $wellKnownUrl;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectRoute = $redirectRoute;
        $this->iconUrl = $iconUrl;
        $this->assumeEmailVerified = $assumeEmailVerified;
        $this->enableRetrieveUserInfo = $enableRetrieveUserInfo;
        $this->additionalScopes = $additionalScopes;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getWellKnownUrl(): ?string
    {
        return $this->wellKnownUrl;
    }

    public function setWellKnownUrl(?string $wellKnownUrl): self
    {
        $this->wellKnownUrl = $wellKnownUrl;

        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(?string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    public function getRedirectRoute(): ?string
    {
        return $this->redirectRoute;
    }

    public function setRedirectRoute(?string $redirectRoute): self
    {
        $this->redirectRoute = $redirectRoute;

        return $this;
    }

    public function getIconUrl(): ?string
    {
        return $this->iconUrl;
    }

    public function setIconUrl(?string $iconUrl): self
    {
        $this->iconUrl = $iconUrl;

        return $this;
    }

    public function isAssumeEmailVerified(bool $returnDefaultValue = true): ?bool
    {
        if (!$this->isConfigured() && $this->assumeEmailVerified === null && $returnDefaultValue) {
            return false;
        }

        return $this->assumeEmailVerified;
    }

    public function setAssumeEmailVerified(?bool $assumeEmailVerified): self
    {
        $this->assumeEmailVerified = $assumeEmailVerified;

        return $this;
    }

    public function isEnableRetrieveUserInfo(bool $returnDefaultValue = true): ?bool
    {
        if (!$this->isConfigured() && $this->enableRetrieveUserInfo === null && $returnDefaultValue) {
            return true;
        }

        return $this->enableRetrieveUserInfo;
    }

    public function setEnableRetrieveUserInfo(?bool $enableRetrieveUserInfo): self
    {
        $this->enableRetrieveUserInfo = $enableRetrieveUserInfo;

        return $this;
    }

    public function getAdditionalScopes(bool $returnDefaultValue = true): ?array
    {
        if (!$this->isConfigured() && $this->additionalScopes === null && $returnDefaultValue) {
            return [];
        }

        return $this->additionalScopes;
    }

    public function setAdditionalScopes(?array $additionalScopes): self
    {
        $this->additionalScopes = $additionalScopes;

        return $this;
    }

    public function isConfigured(): bool
    {
        return ($this->wellKnownUrl !== null);
    }
}

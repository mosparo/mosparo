<?php

namespace Mosparo\Enum;

use Mosparo\Attributes\AuthSourceInfo;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum AuthSource: int implements TranslatableInterface
{
    #[AuthSourceInfo(description: 'authSource.local.description', icon: 'database')]
    case LOCAL = 1;

    #[AuthSourceInfo(description: 'authSource.remoteOidc.description', icon: 'world')]
    case REMOTE_OIDC = 2;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::LOCAL => $translator->trans('authSource.local.label', domain: 'mosparo', locale: $locale),
            self::REMOTE_OIDC => $translator->trans('authSource.remoteOidc.label', domain: 'mosparo', locale: $locale),
        };
    }

    public function getInfo()
    {
        return AuthSourceInfo::from($this);
    }
}

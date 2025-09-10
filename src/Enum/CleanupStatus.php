<?php

namespace Mosparo\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum CleanupStatus: int implements TranslatableInterface
{
    case UNKNOWN = 0;
    case INCOMPLETE = 1;
    case COMPLETE = 2;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::UNKNOWN => $translator->trans('cleanupStatus.unknown.label', domain: 'mosparo', locale: $locale),
            self::INCOMPLETE => $translator->trans('cleanupStatus.incomplete.label', domain: 'mosparo', locale: $locale),
            self::COMPLETE  => $translator->trans('cleanupStatus.complete.label', domain: 'mosparo', locale: $locale),
        };
    }
}
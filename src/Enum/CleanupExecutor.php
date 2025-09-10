<?php

namespace Mosparo\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum CleanupExecutor: int implements TranslatableInterface
{
    case UNKNOWN = 0;
    case CLEANUP_COMMAND = 1;
    case FRONTEND_API = 2;
    case WEB_CRON_JOB = 3;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::UNKNOWN => $translator->trans('cleanupExecutor.unknown.label', domain: 'mosparo', locale: $locale),
            self::CLEANUP_COMMAND  => $translator->trans('cleanupExecutor.cleanupCommand.label', domain: 'mosparo', locale: $locale),
            self::FRONTEND_API => $translator->trans('cleanupExecutor.frontendApi.label', domain: 'mosparo', locale: $locale),
            self::WEB_CRON_JOB => $translator->trans('cleanupExecutor.webCronJob.label', domain: 'mosparo', locale: $locale),
        };
    }
}
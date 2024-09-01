<?php

namespace Mosparo\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum LanguageSource: int implements TranslatableInterface
{
    case BROWSER_FALLBACK = 0;
    case BROWSER_HTML_FALLBACK = 1;
    case HTML_BROWSER_FALLBACK = 2;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::BROWSER_FALLBACK  => $translator->trans('languageSource.browserFallback.label', domain: 'mosparo', locale: $locale),
            self::BROWSER_HTML_FALLBACK => $translator->trans('languageSource.browserHtmlFallback.label', domain: 'mosparo', locale: $locale),
            self::HTML_BROWSER_FALLBACK  => $translator->trans('languageSource.htmlBrowserFallback.label', domain: 'mosparo', locale: $locale),
        };
    }
}
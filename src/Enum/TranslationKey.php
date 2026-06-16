<?php

namespace Mosparo\Enum;

use Mosparo\Attributes\TranslationKeyInfo;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TranslationKey: int implements TranslatableInterface
{
    #[TranslationKeyInfo(frontendKey: 'label')]
    case LABEL = 1;

    #[TranslationKeyInfo(frontendKey: 'accessibility.checkingData')]
    case ACCESSIBILITY_CHECKING_DATA = 2;
    #[TranslationKeyInfo(frontendKey: 'accessibility.dataValid')]
    case ACCESSIBILITY_DATA_VALID = 3;
    #[TranslationKeyInfo(frontendKey: 'accessibility.protectedBy')]
    case ACCESSIBILITY_PROTECTED_BY = 4;

    #[TranslationKeyInfo(frontendKey: 'error.gotNoToken')]
    case ERROR_GOT_NO_TOKEN = 5;
    #[TranslationKeyInfo(frontendKey: 'error.internalError')]
    case ERROR_INTERNAL_ERROR = 6;
    #[TranslationKeyInfo(frontendKey: 'error.noSubmitTokenAvailable')]
    case ERROR_NO_SUBMIT_TOKEN_AVAILABLE = 7;
    #[TranslationKeyInfo(frontendKey: 'error.spamDetected')]
    case ERROR_SPAM_DETECTED = 8;
    #[TranslationKeyInfo(frontendKey: 'error.lockedOut')]
    case ERROR_LOCKED_OUT = 9;
    #[TranslationKeyInfo(frontendKey: 'error.delay')]
    case ERROR_DELAY = 10;

    #[TranslationKeyInfo(frontendKey: 'hp.fieldTitle')]
    case HONEY_POT_FIELD_TITLE = 11;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::LABEL  => $translator->trans('label', domain: 'frontend', locale: $locale),
            self::ACCESSIBILITY_CHECKING_DATA => $translator->trans('accessibility.checkingData', domain: 'frontend', locale: $locale),
            self::ACCESSIBILITY_DATA_VALID  => $translator->trans('accessibility.dataValid', domain: 'frontend', locale: $locale),
            self::ACCESSIBILITY_PROTECTED_BY  => $translator->trans('accessibility.protectedBy', domain: 'frontend', locale: $locale),
            self::ERROR_GOT_NO_TOKEN  => $translator->trans('error.gotNoToken', domain: 'frontend', locale: $locale),
            self::ERROR_INTERNAL_ERROR  => $translator->trans('error.internalError', domain: 'frontend', locale: $locale),
            self::ERROR_NO_SUBMIT_TOKEN_AVAILABLE  => $translator->trans('error.noSubmitTokenAvailable', domain: 'frontend', locale: $locale),
            self::ERROR_SPAM_DETECTED  => $translator->trans('error.spamDetected', domain: 'frontend', locale: $locale),
            self::ERROR_LOCKED_OUT  => $translator->trans('error.lockedOut', domain: 'frontend', locale: $locale),
            self::ERROR_DELAY  => $translator->trans('error.delay', domain: 'frontend', locale: $locale),
            self::HONEY_POT_FIELD_TITLE  => $translator->trans('hp.fieldTitle', domain: 'frontend', locale: $locale),
        };
    }
}
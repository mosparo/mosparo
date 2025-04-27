<?php

namespace Mosparo\Enum;

use Mosparo\Attributes\RulePackageTypeInfo;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum RulePackageType: int implements TranslatableInterface
{
    #[RulePackageTypeInfo(key: 'automaticallyFromUrl', category: RulePackageTypeCategory::AUTOMATICALLY, title: 'rulePackageType.automaticallyFromUrl.label', description: 'rulePackageType.automaticallyFromUrl.description', icon: 'ti ti-world-www')]
    case AUTOMATICALLY_FROM_URL = 1;
    #[RulePackageTypeInfo(key: 'automaticallyFromFile', category: RulePackageTypeCategory::AUTOMATICALLY, title: 'rulePackageType.automaticallyFromFile.label', description: 'rulePackageType.automaticallyFromFile.description', icon: 'ti ti-file-code-2')]
    case AUTOMATICALLY_FROM_FILE = 2;
    #[RulePackageTypeInfo(key: 'manuallyViaCli', category: RulePackageTypeCategory::MANUALLY, title: 'rulePackageType.manuallyViaCli.label', description: 'rulePackageType.manuallyViaCli.description', icon: 'ti ti-terminal-2')]
    case MANUALLY_VIA_CLI = 3;
    #[RulePackageTypeInfo(key: 'manuallyViaApi', category: RulePackageTypeCategory::MANUALLY, title: 'rulePackageType.manuallyViaApi.label', description: 'rulePackageType.manuallyViaApi.description', icon: 'ti ti-api')]
    case MANUALLY_VIA_API = 4;

    public static function list(?RulePackageTypeCategory $category = null): array
    {
        $values = [];
        foreach (self::cases() as $enum) {
            $info = RulePackageTypeInfo::from($enum);

            if ($category && $info->category !== $category) {
                continue;
            }

            $values[] = $info;
        }

        return $values;
    }

    public static function automaticTypes(): array
    {
        return [
            self::AUTOMATICALLY_FROM_URL,
            self::AUTOMATICALLY_FROM_FILE,
        ];
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $info = RulePackageTypeInfo::from($this);

        return $translator->trans($info->title, domain: 'mosparo', locale: $locale);
    }

    public static function fromKey(?string $key): ?self
    {
        if ($key === null) {
            return null;
        }

        foreach (RulePackageType::cases() as $enum) {
            $info = RulePackageTypeInfo::from($enum);

            if ($info->key === $key) {
                return $enum;
            }
        }

        return null;
    }
}
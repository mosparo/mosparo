<?php

namespace Mosparo\Enum;

use Mosparo\Attributes\RulePackageTypeCategoryInfo;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum RulePackageTypeCategory: int implements TranslatableInterface
{
    #[RulePackageTypeCategoryInfo(key: 'automatically', title: 'rulePackageType.category.automatically.label', description: 'rulePackageType.category.automatically.description')]
    case AUTOMATICALLY = 1;

    #[RulePackageTypeCategoryInfo(key: 'manually', title: 'rulePackageType.category.manually.label', description: 'rulePackageType.category.manually.description')]
    case MANUALLY = 2;

    public static function list(): array
    {
        $values = array_map(function ($enum) {
            $info = RulePackageTypeCategoryInfo::from($enum);
            $types = RulePackageType::list($enum);

            return [
                'info' => $info,
                'types' => $types,
            ];
        }, self::cases());

        return $values;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $info = RulePackageTypeCategoryInfo::from($this);

        return $translator->trans($info->title, domain: 'mosparo', locale: $locale);
    }
}
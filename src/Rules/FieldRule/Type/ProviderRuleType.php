<?php

namespace Mosparo\Rules\FieldRule\Type;

use Mosparo\Rules\FieldRule\Tester\ProviderRuleTester;

final class ProviderRuleType extends AbstractRuleType
{
    protected string $key = 'provider';
    protected string $name = 'rules.fieldRule.type.provider.title';
    protected string $description = 'rules.fieldRule.type.provider.shortIntro';
    protected string $icon = 'ti ti-wifi';
    protected array $subtypes = [
        [
            'key' => 'asNumber',
            'name' => 'rules.fieldRule.type.provider.asNumber.title',
        ],
        [
            'key' => 'country',
            'name' => 'rules.fieldRule.type.provider.country.title',
        ],
    ];
    protected string $testerClass = ProviderRuleTester::class;
    protected array $targetFieldKeys = ['client.asNumber', 'client.country'];
    protected string $helpTemplate = 'project_related/rules/field_rule/type/help/provider.html.twig';

    public function getValidatorPattern(): array
    {
        return [
            'asNumber' => '^\d{1,10}$',
            'country' => '^[A-Z]{2}$',
        ];
    }
}
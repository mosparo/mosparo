<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\ProviderFormType;
use Mosparo\Rule\Tester\ProviderRuleTester;

final class ProviderRuleType extends AbstractRuleType
{
    protected string $key = 'provider';
    protected string $name = 'rule.type.provider.title';
    protected string $description = 'rule.type.provider.shortIntro';
    protected string $icon = 'ti ti-wifi';
    protected array $subtypes = [
        [
            'key' => 'asNumber',
            'name' => 'rule.type.provider.asNumber.title',
        ],
        [
            'key' => 'country',
            'name' => 'rule.type.provider.country.title',
        ],
    ];
    protected string $formClass = ProviderFormType::class;
    protected string $testerClass = ProviderRuleTester::class;
    protected array $targetFieldKeys = ['client.asNumber', 'client.country'];
    protected string $helpTemplate = 'project_related/rule/type/help/provider.html.twig';

    public function getValidatorPattern(): array
    {
        return [
            'asNumber' => '^\d{1,10}$',
            'country' => '^[A-Z]{2}$',
        ];
    }
}
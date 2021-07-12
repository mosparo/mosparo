<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\ProviderFormType;
use Mosparo\Rule\Tester\ProviderRuleTester;

final class ProviderRuleType extends AbstractRuleType
{
    protected $key = 'provider';
    protected $name = 'rule.type.provider.title';
    protected $description = 'rule.type.provider.shortIntro';
    protected $icon = 'ti ti-wifi';
    protected $subtypes = [
        [
            'key' => 'asNumber',
            'name' => 'rule.type.provider.asNumber.title',
        ],
        [
            'key' => 'country',
            'name' => 'rule.type.provider.country.title',
        ],
    ];
    protected $formClass = ProviderFormType::class;
    protected $testerClass = ProviderRuleTester::class;
    protected $targetFieldKeys = ['client.asNumber', 'client.country'];
    protected $helpTemplate = 'project_related/rule/type/help/provider.html.twig';
}
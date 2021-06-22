<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\ProviderFormType;
use Mosparo\Rule\Tester\ProviderRuleTester;

final class ProviderRuleType extends AbstractRuleType
{
    protected $key = 'provider';
    protected $name = 'Provider';
    protected $description = 'Allows to filter by provider specific informations';
    protected $icon = 'ti ti-wifi';
    protected $subtypes = [
        [
            'key' => 'ipAddress',
            'name' => 'IP Address',
        ],
        [
            'key' => 'subnet',
            'name' => 'Subnet',
        ],
        [
            'key' => 'asn',
            'name' => 'ASN',
        ],
    ];
    protected $formClass = ProviderFormType::class;
    protected $testerClass = ProviderRuleTester::class;
    protected $targetFieldKeys = ['client.ipAddress'];
    protected $helpTemplate = 'project_related/rule/type/help/provider.html.twig';
}
<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\IpAddressFormType;
use Mosparo\Rule\Tester\IpAddressRuleTester;

final class IpAddressRuleType extends AbstractRuleType
{
    protected $key = 'ipAddress';
    protected $name = 'rule.type.ipAddress.title';
    protected $description = 'rule.type.ipAddress.shortIntro';
    protected $icon = 'ti ti-plug';
    protected $subtypes = [
        [
            'key' => 'ipAddress',
            'name' => 'rule.type.ipAddress.ipAddress.title',
        ],
        [
            'key' => 'subnet',
            'name' => 'rule.type.ipAddress.subnet.title',
        ],
    ];
    protected $formClass = IpAddressFormType::class;
    protected $testerClass = IpAddressRuleTester::class;
    protected $targetFieldKeys = ['client.ipAddress'];
    protected $helpTemplate = 'project_related/rule/type/help/ipAddress.html.twig';
}
<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\IpAddressFormType;
use Mosparo\Rule\Tester\IpAddressRuleTester;

final class IpAddressRuleType extends AbstractRuleType
{
    protected string $key = 'ipAddress';
    protected string $name = 'rule.type.ipAddress.title';
    protected string $description = 'rule.type.ipAddress.shortIntro';
    protected string $icon = 'ti ti-plug';
    protected array $subtypes = [
        [
            'key' => 'ipAddress',
            'name' => 'rule.type.ipAddress.ipAddress.title',
        ],
        [
            'key' => 'subnet',
            'name' => 'rule.type.ipAddress.subnet.title',
        ],
    ];
    protected string $formClass = IpAddressFormType::class;
    protected string $testerClass = IpAddressRuleTester::class;
    protected array $targetFieldKeys = ['client.ipAddress'];
    protected string $helpTemplate = 'project_related/rule/type/help/ipAddress.html.twig';
}
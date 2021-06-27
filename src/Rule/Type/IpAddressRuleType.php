<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\IpAddressFormType;
use Mosparo\Rule\Tester\IpAddressRuleTester;

final class IpAddressRuleType extends AbstractRuleType
{
    protected $key = 'ipAddress';
    protected $name = 'IP Address';
    protected $description = 'Allows to filter by ip addresses and subnets';
    protected $icon = 'ti ti-plug';
    protected $subtypes = [
        [
            'key' => 'ipAddress',
            'name' => 'IP Address',
        ],
        [
            'key' => 'subnet',
            'name' => 'Subnet',
        ],
    ];
    protected $formClass = IpAddressFormType::class;
    protected $testerClass = IpAddressRuleTester::class;
    protected $targetFieldKeys = ['client.ipAddress'];
    protected $helpTemplate = 'project_related/rule/type/help/ipAddress.html.twig';
}
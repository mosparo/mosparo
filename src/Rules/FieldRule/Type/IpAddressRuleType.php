<?php

namespace Mosparo\Rules\FieldRule\Type;

use Mosparo\Rules\FieldRule\Tester\IpAddressRuleTester;

final class IpAddressRuleType extends AbstractRuleType
{
    protected string $key = 'ipAddress';
    protected string $name = 'rules.fieldRule.type.ipAddress.title';
    protected string $description = 'rules.fieldRule.type.ipAddress.shortIntro';
    protected string $icon = 'ti ti-plug';
    protected array $subtypes = [
        [
            'key' => 'ipAddress',
            'name' => 'rules.fieldRule.type.ipAddress.ipAddress.title',
        ],
        [
            'key' => 'subnet',
            'name' => 'rules.fieldRule.type.ipAddress.subnet.title',
        ],
    ];
    protected string $testerClass = IpAddressRuleTester::class;
    protected array $targetFieldKeys = ['client.ipAddress'];
    protected string $helpTemplate = 'project_related/rules/field_rule/type/help/ipAddress.html.twig';

    public function getValidatorPattern(): array
    {
        return [
            'ipAddress' => '^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}|[0-9a-fA-F]{1,4}:(:?:?[0-9a-fA-F]{1,4}){0,7}(::)?)$',
            'subnet' => '^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}|[0-9a-fA-F]{1,4}:(:?:?[0-9a-fA-F]{1,4}){0,7}(::)?)/\d{1,3}$',
        ];
    }
}
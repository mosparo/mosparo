<?php

namespace Mosparo\Rules\FieldRule\Type;

use IPLib\Factory;
use IPLib\Range\Subnet;
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
            // These patterns are pretty open, because we need to correctly validate the addresses in the backend.
            'ipAddress' => '^[0-9a-fA-F:.]+$',
            'subnet' => '^[0-9a-fA-F:.]+/\d{1,3}$',
        ];
    }

    public function isValidValue(string $subtype, string $value): bool
    {
        $initialResult = parent::isValidValue($subtype, $value);

        if (!$initialResult) {
            return false;
        }

        if ($subtype === 'subnet') {
            return Subnet::parseString($value) !== null;
        } else {
            return Factory::parseAddressString($value) !== null;
        }
    }
}
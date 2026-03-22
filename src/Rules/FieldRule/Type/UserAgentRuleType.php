<?php

namespace Mosparo\Rules\FieldRule\Type;

use Mosparo\Rules\FieldRule\Tester\UserAgentRuleTester;

final class UserAgentRuleType extends AbstractRuleType
{
    protected string $key = 'user-agent';
    protected string $name = 'rules.fieldRule.type.userAgent.title';
    protected string $description = 'rules.fieldRule.type.userAgent.shortIntro';
    protected string $icon = 'ti ti-browser';
    protected array $subtypes = [
        [
            'key' => 'uaText',
            'name' => 'rules.fieldRule.type.userAgent.text.title',
        ],
        [
            'key' => 'uaRegex',
            'name' => 'rules.fieldRule.type.userAgent.regex.title'
        ]
    ];
    protected string $testerClass = UserAgentRuleTester::class;
    protected array $targetFieldKeys = ['client.userAgent'];
    protected string $helpTemplate = 'project_related/rules/field_rule/type/help/userAgent.html.twig';
}
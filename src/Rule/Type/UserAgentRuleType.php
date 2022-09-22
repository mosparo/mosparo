<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\UserAgentFormType;
use Mosparo\Rule\Tester\UserAgentRuleTester;

final class UserAgentRuleType extends AbstractRuleType
{
    protected string $key = 'user-agent';
    protected string $name = 'rule.type.userAgent.title';
    protected string $description = 'rule.type.userAgent.shortIntro';
    protected string $icon = 'ti ti-browser';
    protected array $subtypes = [
        [
            'key' => 'text',
            'name' => 'rule.type.userAgent.text.title',
        ],
        [
            'key' => 'regex',
            'name' => 'rule.type.userAgent.regex.title'
        ]
    ];
    protected string $formClass = UserAgentFormType::class;
    protected string $testerClass = UserAgentRuleTester::class;
    protected array $targetFieldKeys = ['client.userAgent'];
    protected string $helpTemplate = 'project_related/rule/type/help/userAgent.html.twig';
}
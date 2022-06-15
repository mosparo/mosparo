<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\DomainFormType;
use Mosparo\Rule\Tester\DomainRuleTester;

final class DomainRuleType extends AbstractRuleType
{
    protected string $key = 'domain';
    protected string $name = 'rule.type.domain.title';
    protected string $description = 'rule.type.domain.shortIntro';
    protected string $icon = 'ti ti-building';
    protected array $subtypes = [
        [
            'key' => 'domain',
            'name' => 'rule.type.domain.domain.title',
        ],
    ];
    protected string $formClass = DomainFormType::class;
    protected string $testerClass = DomainRuleTester::class;
    protected array $targetFieldKeys = ['formData.input[url]', 'formData.input[email]', 'formData.textarea'];
    protected string $helpTemplate = 'project_related/rule/type/help/domain.html.twig';
}
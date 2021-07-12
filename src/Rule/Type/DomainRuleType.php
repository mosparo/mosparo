<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\DomainFormType;
use Mosparo\Rule\Tester\DomainRuleTester;

final class DomainRuleType extends AbstractRuleType
{
    protected $key = 'domain';
    protected $name = 'rule.type.domain.title';
    protected $description = 'rule.type.domain.shortIntro';
    protected $icon = 'ti ti-building';
    protected $subtypes = [
        [
            'key' => 'domain',
            'name' => 'rule.type.domain.domain.title',
        ],
    ];
    protected $formClass = DomainFormType::class;
    protected $testerClass = DomainRuleTester::class;
    protected $targetFieldKeys = ['formData.input[url]', 'formData.input[email]', 'formData.textarea'];
    protected $helpTemplate = 'project_related/rule/type/help/domain.html.twig';
}
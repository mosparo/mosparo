<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Tester\DomainRuleTester;

final class DomainRuleType extends AbstractRuleType
{
    protected string $key = 'domain';
    protected string $name = 'rules.fieldRule.type.domain.title';
    protected string $description = 'rules.fieldRule.type.domain.shortIntro';
    protected string $icon = 'ti ti-building';
    protected array $subtypes = [
        [
            'key' => 'domain',
            'name' => 'rules.fieldRule.type.domain.domain.title',
        ],
    ];
    protected string $testerClass = DomainRuleTester::class;
    protected array $targetFieldKeys = ['formData.input[url]', 'formData.input[email]', 'formData.textarea'];
    protected string $helpTemplate = 'project_related/rules/field_rule/type/help/domain.html.twig';

    public function getValidatorPattern(): array
    {
        return [
            // This pattern tries to match it as good as possible but is not to be 100% precise.
            'domain' => '^([\w\-\.]+\.)*[\w\-\.]+\.\w{2,}$',
        ];
    }
}
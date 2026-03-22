<?php

namespace Mosparo\Rules\FieldRule\Type;

use Mosparo\Rules\FieldRule\Tester\EmailRuleTester;

final class EmailRuleType extends AbstractRuleType
{
    protected string $key = 'email';
    protected string $name = 'rules.fieldRule.type.email.title';
    protected string $description = 'rules.fieldRule.type.email.shortIntro';
    protected string $icon = 'ti ti-at';
    protected array $subtypes = [
        [
            'key' => 'email',
            'name' => 'rules.fieldRule.type.email.email.title',
        ],
    ];
    protected string $testerClass = EmailRuleTester::class;
    protected array $targetFieldKeys = ['formData.input[email]', 'formData.textarea'];
    protected string $helpTemplate = 'project_related/rules/field_rule/type/help/email.html.twig';

    public function getValidatorPattern(): array
    {
        return [
            // This pattern tries to match it as good as possible but is not to be 100% precise.
            'email' => '^[\w\-\.\+]+@([\w-]+\.)+[\w-]{2,}$',
        ];
    }
}
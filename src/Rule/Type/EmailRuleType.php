<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\EmailFormType;
use Mosparo\Rule\Tester\EmailRuleTester;

final class EmailRuleType extends AbstractRuleType
{
    protected string $key = 'email';
    protected string $name = 'rule.type.email.title';
    protected string $description = 'rule.type.email.shortIntro';
    protected string $icon = 'ti ti-at';
    protected array $subtypes = [
        [
            'key' => 'email',
            'name' => 'rule.type.email.email.title',
        ],
    ];
    protected string $formClass = EmailFormType::class;
    protected string $testerClass = EmailRuleTester::class;
    protected array $targetFieldKeys = ['formData.input[email]', 'formData.textarea'];
    protected string $helpTemplate = 'project_related/rule/type/help/email.html.twig';
}
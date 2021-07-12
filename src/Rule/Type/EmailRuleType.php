<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\EmailFormType;
use Mosparo\Rule\Tester\EmailRuleTester;

final class EmailRuleType extends AbstractRuleType
{
    protected $key = 'email';
    protected $name = 'rule.type.email.title';
    protected $description = 'rule.type.email.shortIntro';
    protected $icon = 'ti ti-at';
    protected $subtypes = [
        [
            'key' => 'email',
            'name' => 'rule.type.email.email.title',
        ],
    ];
    protected $formClass = EmailFormType::class;
    protected $testerClass = EmailRuleTester::class;
    protected $targetFieldKeys = ['formData.input[email]', 'formData.textarea'];
    protected $helpTemplate = 'project_related/rule/type/help/email.html.twig';
}
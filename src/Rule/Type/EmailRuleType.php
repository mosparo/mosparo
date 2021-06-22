<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\EmailFormType;
use Mosparo\Rule\Tester\EmailRuleTester;

final class EmailRuleType extends AbstractRuleType
{
    protected $key = 'email';
    protected $name = 'Email';
    protected $description = 'Allows to filter by specific email addresses';
    protected $icon = 'ti ti-at';
    protected $subtypes = [
        [
            'key' => 'email',
            'name' => 'Email',
        ],
    ];
    protected $formClass = EmailFormType::class;
    protected $testerClass = EmailRuleTester::class;
    protected $targetFieldKeys = ['formData.input[email]', 'formData.textarea'];
    protected $helpTemplate = 'project_related/rule/type/help/email.html.twig';
}
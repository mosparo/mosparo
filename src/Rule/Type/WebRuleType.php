<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\WebFormType;
use Mosparo\Rule\Tester\WebRuleTester;

final class WebRuleType extends AbstractRuleType
{
    protected $key = 'web';
    protected $name = 'Web';
    protected $description = 'Allows to filter by specific domains or URLs';
    protected $icon = 'ti ti-world';
    protected $subtypes = [
        [
            'key' => 'domain',
            'name' => 'Domain',
        ],
        [
            'key' => 'url',
            'name' => 'URL'
        ],
    ];
    protected $formClass = WebFormType::class;
    protected $testerClass = WebRuleTester::class;
    protected $targetFieldKeys = ['formData.input[url]', 'formData.textarea'];
    protected $helpTemplate = 'project_related/rule/type/help/web.html.twig';
}
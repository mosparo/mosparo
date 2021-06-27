<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\WebsiteFormType;
use Mosparo\Rule\Tester\WebsiteRuleTester;

final class WebsiteRuleType extends AbstractRuleType
{
    protected $key = 'website';
    protected $name = 'Website';
    protected $description = 'Allows to filter by specific websites';
    protected $icon = 'ti ti-world';
    protected $subtypes = [
        [
            'key' => 'website',
            'name' => 'Website'
        ],
    ];
    protected $formClass = WebsiteFormType::class;
    protected $testerClass = WebsiteRuleTester::class;
    protected $targetFieldKeys = ['formData.input[url]', 'formData.textarea'];
    protected $helpTemplate = 'project_related/rule/type/help/website.html.twig';
}
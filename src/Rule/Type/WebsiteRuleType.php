<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\WebsiteFormType;
use Mosparo\Rule\Tester\WebsiteRuleTester;

final class WebsiteRuleType extends AbstractRuleType
{
    protected $key = 'website';
    protected $name = 'rule.type.website.title';
    protected $description = 'rule.type.website.shortIntro';
    protected $icon = 'ti ti-world';
    protected $subtypes = [
        [
            'key' => 'url',
            'name' => 'rule.type.website.url.title'
        ],
    ];
    protected $formClass = WebsiteFormType::class;
    protected $testerClass = WebsiteRuleTester::class;
    protected $targetFieldKeys = ['formData.input[url]', 'formData.textarea'];
    protected $helpTemplate = 'project_related/rule/type/help/website.html.twig';
}
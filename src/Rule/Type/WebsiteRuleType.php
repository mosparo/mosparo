<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\WebsiteFormType;
use Mosparo\Rule\Tester\WebsiteRuleTester;

final class WebsiteRuleType extends AbstractRuleType
{
    protected string $key = 'website';
    protected string $name = 'rule.type.website.title';
    protected string $description = 'rule.type.website.shortIntro';
    protected string $icon = 'ti ti-world';
    protected array $subtypes = [
        [
            'key' => 'url',
            'name' => 'rule.type.website.url.title'
        ],
    ];
    protected string $formClass = WebsiteFormType::class;
    protected string $testerClass = WebsiteRuleTester::class;
    protected array $targetFieldKeys = ['formData.input[url]', 'formData.textarea'];
    protected string $helpTemplate = 'project_related/rule/type/help/website.html.twig';
}
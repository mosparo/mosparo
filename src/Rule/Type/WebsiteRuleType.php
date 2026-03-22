<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Tester\WebsiteRuleTester;

final class WebsiteRuleType extends AbstractRuleType
{
    protected string $key = 'website';
    protected string $name = 'rules.fieldRule.type.website.title';
    protected string $description = 'rules.fieldRule.type.website.shortIntro';
    protected string $icon = 'ti ti-world';
    protected array $subtypes = [
        [
            'key' => 'url',
            'name' => 'rules.fieldRule.type.website.url.title'
        ],
    ];
    protected string $testerClass = WebsiteRuleTester::class;
    protected array $targetFieldKeys = ['formData.input[url]', 'formData.textarea'];
    protected string $helpTemplate = 'project_related/rules/field_rule/type/help/website.html.twig';

    public function getValidatorPattern(): array
    {
        return [
            // This pattern tries to match it as good as possible but is not to be 100% precise.
            'url' => '^([a-zA-Z0-9]+)?:\/\/([\w\-\.]+\.)*[\w\-\.]+\.\w{2,}(.[^\s]*)',
        ];
    }
}
<?php

namespace Mosparo\Rules\FieldRule\Type;

use Mosparo\Rules\FieldRule\Tester\WordRuleTester;

final class WordRuleType extends AbstractRuleType
{
    protected string $key = 'word';
    protected string $name = 'rules.fieldRule.type.word.title';
    protected string $description = 'rules.fieldRule.type.word.shortIntro';
    protected string $icon = 'ti ti-forms';
    protected array $subtypes = [
        [
            'key' => 'text',
            'name' => 'rules.fieldRule.type.word.text.title',
        ],
        [
            'key' => 'regex',
            'name' => 'rules.fieldRule.type.word.regex.title'
        ]
    ];
    protected string $testerClass = WordRuleTester::class;
    protected array $targetFieldKeys = ['formData.'];
    protected string $helpTemplate = 'project_related/rules/field_rule/type/help/word.html.twig';
}
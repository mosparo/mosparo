<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Tester\WordRuleTester;

final class WordRuleType extends AbstractRuleType
{
    protected string $key = 'word';
    protected string $name = 'rule.type.word.title';
    protected string $description = 'rule.type.word.shortIntro';
    protected string $icon = 'ti ti-forms';
    protected array $subtypes = [
        [
            'key' => 'text',
            'name' => 'rule.type.word.text.title',
        ],
        [
            'key' => 'wExact',
            'name' => 'rule.type.word.wExact.title',
        ],
        [
            'key' => 'wFull',
            'name' => 'rule.type.word.wFull.title',
        ],
        [
            'key' => 'regex',
            'name' => 'rule.type.word.regex.title'
        ]
    ];
    protected string $testerClass = WordRuleTester::class;
    protected array $targetFieldKeys = ['formData.'];
    protected string $helpTemplate = 'project_related/rule/type/help/word.html.twig';
}
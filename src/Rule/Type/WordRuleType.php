<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\WordFormType;
use Mosparo\Rule\Tester\WordRuleTester;

final class WordRuleType extends AbstractRuleType
{
    protected $key = 'word';
    protected $name = 'rule.type.word.title';
    protected $description = 'rule.type.word.shortIntro';
    protected $icon = 'ti ti-forms';
    protected $subtypes = [
        [
            'key' => 'text',
            'name' => 'rule.type.word.text.title',
        ],
        [
            'key' => 'regex',
            'name' => 'rule.type.word.regex.title'
        ]
    ];
    protected $formClass = WordFormType::class;
    protected $testerClass = WordRuleTester::class;
    protected $targetFieldKeys = ['formData.'];
    protected $helpTemplate = 'project_related/rule/type/help/word.html.twig';
}
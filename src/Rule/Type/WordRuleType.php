<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\WordFormType;
use Mosparo\Rule\Tester\WordRuleTester;

final class WordRuleType extends AbstractRuleType
{
    protected $key = 'word';
    protected $name = 'Word';
    protected $description = 'Allows to filter by specific words or patterns';
    protected $icon = 'ti ti-forms';
    protected $subtypes = [
        [
            'key' => 'text',
            'name' => 'Text',
        ],
        [
            'key' => 'regex',
            'name' => 'RegEx'
        ]
    ];
    protected $formClass = WordFormType::class;
    protected $testerClass = WordRuleTester::class;
    protected $targetFieldKeys = ['formData.'];
    protected $helpTemplate = 'project_related/rule/type/help/word.html.twig';
}
<?php

namespace Mosparo\Rule\Type;

use Mosparo\Rule\Form\UnicodeBlockFormType;
use Mosparo\Rule\Tester\UnicodeBlockRuleTester;
use zepi\Unicode\UnicodeIndex;

final class UnicodeBlockRuleType extends AbstractRuleType
{
    protected string $key = 'unicodeBlock';
    protected string $name = 'rule.type.unicodeBlock.title';
    protected string $description = 'rule.type.unicodeBlock.shortIntro';
    protected string $icon = 'ti ti-language';
    protected array $subtypes = [
        [
            'key' => 'block',
            'name' => 'rule.type.unicodeBlock.block.title',
        ],
    ];
    protected string $formClass = UnicodeBlockFormType::class;
    protected string $testerClass = UnicodeBlockRuleTester::class;
    protected array $targetFieldKeys = ['formData.'];
    protected string $helpTemplate = 'project_related/rule/type/help/unicodeBlock.html.twig';
    protected bool $allowAddMultiple = false;

    public function formatValue(string $value, string $locale = ''): string
    {
        $unicodeIndex = new UnicodeIndex();
        $unicodeBlock = $unicodeIndex->getBlockByKey($value);
        if (!$unicodeBlock) {
            return $value;
        }

        return $unicodeBlock->getName($locale);
    }
}
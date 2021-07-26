<?php

namespace Mosparo\Tests\Rule\Type;

use Mosparo\Rule\Type\AbstractRuleType;

final class TestRuleType extends AbstractRuleType
{
    protected $key = 'test-type';
    protected $name = 'test-type-name';
    protected $description = 'test-type-description';
    protected $icon = 'test-type-icon';
    protected $subtypes = [
        [
            'key' => 'test-type-subtype-1',
            'name' => 'test-type-subtype-1.title',
        ]
    ];
    protected $formClass = 'test-type-form-class';
    protected $testerClass = 'test-type-tester-class';
    protected $targetFieldKeys = ['formData.'];
    protected $helpTemplate = 'test-type-help-template';
}
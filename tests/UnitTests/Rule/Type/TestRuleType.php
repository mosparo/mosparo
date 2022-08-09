<?php

namespace Mosparo\Tests\UnitTests\Rule\Type;

use Mosparo\Rule\Type\AbstractRuleType;

final class TestRuleType extends AbstractRuleType
{
    protected string $key = 'test-type';
    protected string $name = 'test-type-name';
    protected string $description = 'test-type-description';
    protected string $icon = 'test-type-icon';
    protected array $subtypes = [
        [
            'key' => 'test-type-subtype-1',
            'name' => 'test-type-subtype-1.title',
        ]
    ];
    protected string $formClass = 'test-type-form-class';
    protected string $testerClass = 'test-type-tester-class';
    protected array $targetFieldKeys = ['formData.'];
    protected string $helpTemplate = 'test-type-help-template';
}
<?php

namespace Mosparo\Tests\UnitTests\Rule\Type;

use Mosparo\Rule\Type\AbstractRuleType;

final class SecondTestRuleType extends AbstractRuleType
{
    protected string $key = 'second-test-type';
    protected string $name = 'second-test-type-name';
    protected string $description = 'second-test-type-description';
    protected string $icon = 'second-test-type-icon';
    protected array $subtypes = [
        [
            'key' => 'second-test-type-subtype-1',
            'name' => 'second-test-type-subtype-1.title',
        ]
    ];
    protected string $formClass = 'second-test-type-form-class';
    protected string $testerClass = 'second-test-type-tester-class';
    protected array $targetFieldKeys = ['formData.'];
    protected string $helpTemplate = 'second-test-type-help-template';
}
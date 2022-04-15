<?php

namespace Mosparo\Tests\Rule\Type;

use PHPUnit\Framework\TestCase;

class AbstractRuleTypeTest extends TestCase
{
    public function testValidateDataWord()
    {
        $ruleType = new TestRuleType();

        $this->assertEquals('test-type', $ruleType->getKey());
        $this->assertEquals('test-type-name', $ruleType->getName());
        $this->assertEquals('test-type-description', $ruleType->getDescription());
        $this->assertEquals('test-type-icon', $ruleType->getIcon());
        $this->assertEquals([
            [
                'key' => 'test-type-subtype-1',
                'name' => 'test-type-subtype-1.title',
            ]
        ], $ruleType->getSubtypes());
        $this->assertEquals('test-type-form-class', $ruleType->getFormClass());
        $this->assertEquals('test-type-tester-class', $ruleType->getTesterClass());
        $this->assertEquals(['formData.'], $ruleType->getTargetFieldKeys());
        $this->assertEquals('test-type-help-template', $ruleType->getHelpTemplate());
    }
}

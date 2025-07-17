<?php

namespace Mosparo\Tests\UnitTests\Rule;

use Mosparo\Rule\RuleTypeManager;
use Mosparo\Rule\Type\AbstractRuleType;
use Mosparo\Tests\UnitTests\Rule\Type\SecondTestRuleType;
use Mosparo\Tests\UnitTests\Rule\Type\TestRuleType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;

class RuleTypeManagerTest extends TestCase
{
    protected function createRuleTypeManager()
    {
        $iterator = function () {
            yield new TestRuleType();
            yield new SecondTestRuleType();
        };

        $rewindableGenerator = $this->createMock(RewindableGenerator::class);
        $rewindableGenerator
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator());

        return new RuleTypeManager($rewindableGenerator);
    }

    public function testGetRuleTypes()
    {
        $ruleTypeManager = $this->createRuleTypeManager();

        $this->assertCount(2, $ruleTypeManager->getRuleTypes());
        $this->assertContainsOnlyInstancesOf(AbstractRuleType::class, $ruleTypeManager->getRuleTypes());
    }

    public function testGetRuleType()
    {
        $this->assertInstanceOf(TestRuleType::class, $this->createRuleTypeManager()->getRuleType('test-type'));
    }

    public function testGetNonExistingRuleType()
    {
        $this->assertNull($this->createRuleTypeManager()->getRuleType('third-test-type'));
    }

    public function testGetRuleTypeKeys()
    {
        $keys = $this->createRuleTypeManager()->getRuleTypeKeys();

        $this->assertCount(2, $keys);
        $this->assertContainsOnly('string', $keys, true);
        $this->assertContains('test-type', $keys);
        $this->assertContains('second-test-type', $keys);
    }
}

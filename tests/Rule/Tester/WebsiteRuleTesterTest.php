<?php

namespace Mosparo\Tests\Rule\Tester;

use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Rule\Tester\WebsiteRuleTester;

class WebsiteRuleTesterTest extends TestCaseWithItems
{
    public function testValidateDataWebsite()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'website', 'value' => '//example.com/test/', 'rating' => 5.0]
            ]));

        $ruleTester = new WebsiteRuleTester();
        $result = $ruleTester->validateData('test', 'http://example.com/test/test.html', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'website', 'value' => '//example.com/test/', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataWebsiteWithoutProtocol()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'website', 'value' => 'example.com/test/test.html', 'rating' => 5.0]
            ]));

        $ruleTester = new WebsiteRuleTester();
        $result = $ruleTester->validateData('test', 'http://example.com/test/test.html', $ruleStub);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([['type' => 'website', 'value' => 'example.com/test/test.html', 'rating' => 5.0, 'uuid' => null]], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);
        $ruleStub
            ->method('getItems')
            ->willReturn($this->buildItemsCollection(RuleItem::class, [
                ['type' => 'website', 'value' => '//example.com/test/test.html', 'rating' => 5.0],
            ]));

        $ruleTester = new WebsiteRuleTester();
        $result = $ruleTester->validateData('test', '//test.com/test/test.html', $ruleStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}

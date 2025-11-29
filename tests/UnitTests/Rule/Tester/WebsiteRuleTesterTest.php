<?php

namespace Mosparo\Tests\UnitTests\Rule\Tester;

use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Rule\Tester\WebsiteRuleTester;

class WebsiteRuleTesterTest extends TestCaseWithItems
{
    public function testValidateDataWebsite()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('website');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('//example.com/test/');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WebsiteRuleTester();
        $result = $ruleTester->validateData('test', 'http://example.com/test/test.html', 'http://example.com/test/test.html', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'website', 'value' => '//example.com/test/', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataWebsiteWithoutProtocol()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('website');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('example.com/test/test.html');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WebsiteRuleTester();
        $result = $ruleTester->validateData('test', 'http://example.com/test/test.html', 'http://example.com/test/test.html', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'website', 'value' => 'example.com/test/test.html', 'rating' => 5.0, 'uuid' => null], $result);
    }

    public function testValidateDataNothingFound()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('website');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('example.com/test/test.html');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WebsiteRuleTester();
        $result = $ruleTester->validateData('test', '//test.com/test/test.html', '//test.com/test/test.html', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @see https://github.com/mosparo/mosparo/issues/380
     */
    public function testValidateDataWebsiteWithUppercaseProtocol()
    {
        $ruleStub = $this->createStub(Rule::class);

        $ruleItemStub = $this->createStub(RuleItem::class);
        $ruleItemStub
            ->method('getType')
            ->willReturn('website');
        $ruleItemStub
            ->method('getValue')
            ->willReturn('HTTP://example.com/test/test.html');
        $ruleItemStub
            ->method('getSpamRatingFactor')
            ->willReturn(5.0);
        $ruleItemStub
            ->method('getParent')
            ->willReturn($ruleStub);

        $ruleTester = new WebsiteRuleTester();
        $result = $ruleTester->validateData('test', 'http://example.com/test/test.html', 'http://example.com/test/test.html', $ruleItemStub);

        $this->assertIsArray($result);
        $this->assertEquals(['type' => 'website', 'value' => 'HTTP://example.com/test/test.html', 'rating' => 5.0, 'uuid' => null], $result);
    }
}

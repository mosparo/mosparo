<?php

namespace Mosparo\Tests\UnitTests\Rule\Form;

use Mosparo\Rule\Form\WebsiteFormType;
use Mosparo\Rule\Type\WebsiteRuleType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;

class WebsiteFormTypeTest extends TestCase
{
    public function testInitializeWebsiteForm()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->exactly(3))
            ->method('add')
            ->willReturn($this->returnSelf());

        $formType = new WebsiteFormType();
        $formType->buildForm($formBuilderStub, ['rule_type' => new WebsiteRuleType()]);
    }

    public function testInitializeWebsiteFormWithoutRuleType()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->never())
            ->method('add')
            ->willReturn($this->returnSelf());

        $formType = new WebsiteFormType();
        $formType->buildForm($formBuilderStub, ['rule_type' => null]);
    }
}

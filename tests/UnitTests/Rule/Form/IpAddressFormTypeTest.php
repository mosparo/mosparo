<?php

namespace Mosparo\Tests\UnitTests\Rule\Form;

use Mosparo\Rule\Form\IpAddressFormType;
use Mosparo\Rule\Type\IpAddressRuleType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;

class IpAddressFormTypeTest extends TestCase
{
    public function testInitializeIpAddressForm()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->exactly(3))
            ->method('add')
            ->willReturn($this->returnSelf());

        $formType = new IpAddressFormType();
        $formType->buildForm($formBuilderStub, ['rule_type' => new IpAddressRuleType()]);
    }

    public function testInitializeIpAddressFormWithoutRuleType()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->never())
            ->method('add')
            ->willReturn($this->returnSelf());

        $formType = new IpAddressFormType();
        $formType->buildForm($formBuilderStub, ['rule_type' => null]);
    }
}

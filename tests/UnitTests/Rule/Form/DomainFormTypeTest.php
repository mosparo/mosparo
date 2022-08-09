<?php

namespace Mosparo\Tests\UnitTests\Rule\Form;

use Mosparo\Rule\Form\DomainFormType;
use Mosparo\Rule\Type\DomainRuleType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;

class DomainFormTypeTest extends TestCase
{
    public function testInitializeDomainForm()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->exactly(3))
            ->method('add')
            ->willReturn($this->returnSelf());

        $formType = new DomainFormType();
        $formType->buildForm($formBuilderStub, ['rule_type' => new DomainRuleType()]);
    }

    public function testInitializeDomainFormWithoutRuleType()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->never())
            ->method('add')
            ->willReturn($this->returnSelf());

        $formType = new DomainFormType();
        $formType->buildForm($formBuilderStub, ['rule_type' => null]);
    }
}

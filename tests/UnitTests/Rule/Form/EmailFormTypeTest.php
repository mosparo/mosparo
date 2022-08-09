<?php

namespace Mosparo\Tests\UnitTests\Rule\Form;

use Mosparo\Rule\Form\EmailFormType;
use Mosparo\Rule\Type\EmailRuleType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;

class EmailFormTypeTest extends TestCase
{
    public function testInitializeEmailForm()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->exactly(3))
            ->method('add')
            ->willReturn($this->returnSelf());

        $formType = new EmailFormType();
        $formType->buildForm($formBuilderStub, ['rule_type' => new EmailRuleType()]);
    }

    public function testInitializeEmailFormWithoutRuleType()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->never())
            ->method('add')
            ->willReturn($this->returnSelf());

        $formType = new EmailFormType();
        $formType->buildForm($formBuilderStub, ['rule_type' => null]);
    }
}

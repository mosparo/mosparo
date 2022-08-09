<?php

namespace Mosparo\Tests\UnitTests\Rule\Form;

use Mosparo\Rule\Form\ProviderFormType;
use Mosparo\Rule\Type\ProviderRuleType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;

class ProviderFormTypeTest extends TestCase
{
    public function testInitializeProviderForm()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->exactly(3))
            ->method('add')
            ->willReturn($this->returnSelf());

        $formType = new ProviderFormType();
        $formType->buildForm($formBuilderStub, ['rule_type' => new ProviderRuleType()]);
    }

    public function testInitializeProviderFormWithoutRuleType()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->never())
            ->method('add')
            ->willReturn($this->returnSelf());

        $formType = new ProviderFormType();
        $formType->buildForm($formBuilderStub, ['rule_type' => null]);
    }
}

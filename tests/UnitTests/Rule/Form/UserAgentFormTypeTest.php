<?php

namespace Mosparo\Tests\UnitTests\Rule\Form;

use Mosparo\Entity\RuleItem;
use Mosparo\Rule\Form\UserAgentFormType;
use Mosparo\Rule\Type\UserAgentRuleType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;

class UserAgentFormTypeTest extends TestCase
{
    public function testInitializeUserAgentForm()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->exactly(3))
            ->method('add')
            ->willReturn($this->returnSelf());

        $translatorStub = $this->createMock(Translator::class);

        $formType = new UserAgentFormType($translatorStub);
        $formType->buildForm($formBuilderStub, ['rule_type' => new UserAgentRuleType()]);
    }

    public function testInitializeUserAgentFormWithoutRuleType()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->never())
            ->method('add')
            ->willReturn($this->returnSelf());

        $translatorStub = $this->createMock(Translator::class);

        $formType = new UserAgentFormType($translatorStub);
        $formType->buildForm($formBuilderStub, ['rule_type' => null]);
    }

    public function testConfigureOptions()
    {
        $formBuilderStub = $this->createMock(FormBuilder::class);
        $formBuilderStub
            ->expects($this->never())
            ->method('add')
            ->willReturn($this->returnSelf());

        $translatorStub = $this->createMock(Translator::class);

        $formType = new UserAgentFormType($translatorStub);

        $optionsResolverStub = $this->createMock(OptionsResolver::class);
        $optionsResolverStub
            ->expects($this->exactly(2))
            ->method('setDefaults')
            ->withConsecutive(
                [
                    [
                        'rule_type' => null,
                        'data_class' => RuleItem::class,
                        'locale' => null,
                    ]
                ],
                [
                    $this->arrayHasKey('constraints')
                ]
            );

        $formType->configureOptions($optionsResolverStub);
    }
}

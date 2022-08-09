<?php

namespace Mosparo\Tests\UnitTests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mosparo\Entity\Project;
use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class RuleTest extends TestCase
{
    const EXAMPLE_UUID = '00000000-0000-0000-0000-000000000000';

    public function testGetId()
    {
        $rule = new Rule();

        $refObject = new ReflectionObject($rule);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rule, '123');

        $this->assertEquals(123, $rule->getId());
    }

    public function testGetUuid()
    {
        $rule = new Rule();
        $rule->setUuid(self::EXAMPLE_UUID);

        $this->assertEquals(self::EXAMPLE_UUID, $rule->getUuid());
    }

    public function testGetName()
    {
        $rule = new Rule();
        $rule->setName('test');

        $this->assertEquals('test', $rule->getName());
    }

    public function testGetDescription()
    {
        $rule = new Rule();
        $rule->setDescription('test-description');

        $this->assertEquals('test-description', $rule->getDescription());
    }

    public function testGetType()
    {
        $rule = new Rule();
        $rule->setType('test-type');

        $this->assertEquals('test-type', $rule->getType());
    }

    public function testGetItems()
    {
        $arrayCollection = new ArrayCollection([
            new RuleItem(),
            new RuleItem()
        ]);

        $rule = new Rule();

        $refObject = new ReflectionObject($rule);
        $refProperty = $refObject->getProperty('items');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rule, $arrayCollection);

        $this->assertSame($arrayCollection, $rule->getItems());
    }

    public function testAddItem()
    {
        $arrayCollection = new ArrayCollection([
            new RuleItem(),
            new RuleItem(),
        ]);

        $rule = new Rule();

        $refObject = new ReflectionObject($rule);
        $refProperty = $refObject->getProperty('items');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rule, $arrayCollection);

        $rule->addItem(new RuleItem);

        $this->assertCount(3, $rule->getItems());
        $this->assertSame($arrayCollection, $rule->getItems());
    }

    public function testRemoveItem()
    {
        $itemToRemove = new RuleItem();
        $arrayCollection = new ArrayCollection([
            new RuleItem(),
            $itemToRemove,
            new RuleItem(),
        ]);

        $rule = new Rule();

        $itemToRemove->setRule($rule);

        $refObject = new ReflectionObject($rule);
        $refProperty = $refObject->getProperty('items');
        $refProperty->setAccessible(true);
        $refProperty->setValue($rule, $arrayCollection);

        $rule->removeItem($itemToRemove);

        $this->assertCount(2, $rule->getItems());
        $this->assertSame($arrayCollection, $rule->getItems());
    }

    public function testGetStatus()
    {
        $rule = new Rule();
        $rule->setStatus(1);

        $this->assertEquals(1, $rule->getStatus());
    }

    public function testGetSpamRatingFactor()
    {
        $rule = new Rule();
        $rule->setSpamRatingFactor(5);

        $this->assertEquals(5, $rule->getSpamRatingFactor());
    }

    public function testGetProject()
    {
        $project = new Project();

        $rule = new Rule();
        $rule->setProject($project);

        $this->assertSame($project, $rule->getProject());
    }
}

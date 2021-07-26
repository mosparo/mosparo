<?php

namespace Mosparo\Tests\Rule\Tester;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

abstract class TestCaseWithItems extends TestCase
{
    protected function buildItemsCollection($className, $items)
    {
        $col = new ArrayCollection();

        foreach ($items as $item) {
            $obj = new $className();
            $obj->setType($item['type']);
            $obj->setValue($item['value']);
            $obj->setSpamRatingFactor($item['rating']);

            $col->add($obj);
        }

        return $col;
    }
}
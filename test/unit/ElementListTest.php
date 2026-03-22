<?php

declare(strict_types=1);

/**
 * Copyright 2007-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Xml
 * @subpackage UnitTests
 */

namespace Horde\Xml\Element\Test;

use Horde\Xml\Element\Element;
use Horde\Xml\Element\ElementList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ElementList::class)]
class ElementListTest extends TestCase
{
    protected ElementListTestList $list;

    public function setUp(): void
    {
        $this->list = new ElementListTestList(
            '<?xml version="1.0" encoding="utf-8"?><list><item>1</item><item>2</item></list>'
        );
    }

    public function testRewind(): void
    {
        $times = 0;
        foreach ($this->list as $i) {
            ++$times;
        }

        $times2 = 0;
        foreach ($this->list as $i) {
            ++$times2;
        }

        $this->assertEquals($times, $times2, 'List should have the same number of iterations multiple times through');
    }

    public function testCurrent(): void
    {
        foreach ($this->list as $i) {
            $this->assertInstanceOf(Element::class, $i, 'Each list item should be an instance of Element');
            break;
        }
    }

    public function testKey(): void
    {
        $keys = [];
        foreach ($this->list as $k => $i) {
            $keys[] = $k;
        }
        $this->assertEquals([0, 1], $keys, 'List should have keys 0 and 1');
    }

    public function testNext(): void
    {
        $last = null;
        foreach ($this->list as $current) {
            $this->assertFalse($last === $current, 'Iteration should produce a new object each item');
            $last = $current;
        }
    }

    public function testCount(): void
    {
        $this->assertEquals(2, $this->list->count(), 'List count should be 2');
    }

    public function testEmptyList(): void
    {
        $emptyList = new ElementListTestList('<list></list>');
        $this->assertEquals(0, $emptyList->count(), 'Empty list count should be 0');

        $iterations = 0;
        foreach ($emptyList as $item) {
            ++$iterations;
        }
        $this->assertEquals(0, $iterations, 'Empty list should not iterate');
    }

    public function testValid(): void
    {
        $this->list->rewind();
        $this->assertTrue($this->list->valid(), 'Iterator should be valid at start');

        $this->list->next();
        $this->list->next();
        $this->assertFalse($this->list->valid(), 'Iterator should be invalid after end');
    }

    public function testMultipleIterations(): void
    {
        $firstPass = [];
        foreach ($this->list as $item) {
            $firstPass[] = (string)$item;
        }

        $secondPass = [];
        foreach ($this->list as $item) {
            $secondPass[] = (string)$item;
        }

        $this->assertEquals($firstPass, $secondPass, 'Multiple iterations should produce same results');
    }
}

/**
 * Test helper class for ElementList.
 */
class ElementListTestList extends ElementList
{
    public function buildListItemCache(): array
    {
        $results = [];
        foreach ($this->element->childNodes as $child) {
            if ($child->localName === 'item') {
                $results[] = $child;
            }
        }

        return $results;
    }
}

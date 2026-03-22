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

use DOMElement;
use Horde\Xml\Element\Element;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use DOMDocument;

#[CoversClass(Element::class)]
class SerializationTest extends TestCase
{
    public function testSerializeElement(): void
    {
        $elt = new Element('<entry><title>Test</title></entry>');
        $serialized = serialize($elt);

        $this->assertIsString($serialized);
        $this->assertStringContainsString('Test', $serialized);
    }

    public function testUnserializeElement(): void
    {
        $elt = new Element('<entry><title>Test</title></entry>');
        $serialized = serialize($elt);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(Element::class, $unserialized);
        $this->assertEquals('Test', $unserialized->title());
    }

    public function testSerializePreservesContent(): void
    {
        $xml = '<feed><title>My Feed</title><entry><title>Entry 1</title></entry><entry><title>Entry 2</title></entry></feed>';
        $element = new Element($xml);

        $serialized = serialize($element);
        $unserialized = unserialize($serialized);

        $this->assertEquals('My Feed', $unserialized->title());
        $this->assertCount(2, $unserialized->entry);
        $this->assertEquals('Entry 1', $unserialized->entry[0]->title());
        $this->assertEquals('Entry 2', $unserialized->entry[1]->title());
    }

    public function testWakeupParsesString(): void
    {
        $elt = new Element('<entry><title>Test</title></entry>');
        $serialized = serialize($elt);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(DOMElement::class, $unserialized->getDom());
        $this->assertEquals('entry', $unserialized->getDom()->tagName);
    }

    public function testWakeupHandlesDomElement(): void
    {
        $doc = new DOMDocument();
        $domEl = $doc->createElement('test');
        $doc->appendChild($domEl);

        $element = new Element($domEl);
        $this->assertSame($domEl, $element->getDom());

        $serialized = serialize($element);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(DOMElement::class, $unserialized->getDom());
        $this->assertEquals('test', $unserialized->getDom()->tagName);
    }
}

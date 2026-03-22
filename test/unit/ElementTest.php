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
use Horde\Xml\Element\Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Element::class)]
class ElementTest extends TestCase
{
    protected Element $element;
    protected Element $namespacedElement;

    public function setUp(): void
    {
        $this->element = new Element(file_get_contents(__DIR__ . '/fixtures/Sample.xml'));
        $this->namespacedElement = new Element(file_get_contents(__DIR__ . '/fixtures/NamespacedSample.xml'));
    }

    public function testXml(): void
    {
        $el = new Element('<root href="#">Root</root>');
        $this->assertEquals('Root', (string) $el);
        $this->assertEquals('#', $el['href']);
    }

    public function testInvalidXml(): void
    {
        $this->expectException(Exception::class);
        new Element('<root');
    }

    public function testSerialization(): void
    {
        $elt = new Element('<entry><title>Test</title></entry>');
        $this->assertEquals('Test', $elt->title(), 'Value before serialization/unserialization');
        $serialized = serialize($elt);
        $unserialized = unserialize($serialized);
        $this->assertEquals('Test', $unserialized->title(), 'Value after serialization/unserialization');
    }

    public function testArrayGet(): void
    {
        $this->assertTrue(is_array($this->element->entry));
        $this->assertTrue(is_array($this->namespacedElement->entry));

        foreach ($this->element->entry as $entry) {
            $this->assertInstanceOf(Element::class, $entry);
        }

        foreach ($this->namespacedElement->entry as $entry) {
            $this->assertInstanceOf(Element::class, $entry);
        }
    }

    public function testOffsetExists(): void
    {
        $this->assertFalse(isset($this->element[-1]), 'Negative array access should fail');
        $this->assertTrue(isset($this->element['version']), 'Version should be set');

        $this->assertFalse(isset($this->namespacedElement[-1]), 'Negative array access should fail');
        $this->assertTrue(isset($this->namespacedElement['version']), 'Version should be set');
    }

    public function testOffsetGet(): void
    {
        $this->assertEquals('1.0', $this->element['version'], 'Version should be 1.0');
        $this->assertEquals('1.0', $this->namespacedElement['version'], 'Version should be 1.0');
    }

    public function testOffsetSet(): void
    {
        $this->element['category'] = 'tests';
        $this->assertTrue(isset($this->element['category']), 'Category should be set');
        $this->assertEquals('tests', $this->element['category'], 'Category should be tests');

        $this->namespacedElement['atom:category'] = 'tests';
        $this->assertTrue(isset($this->namespacedElement['atom:category']), 'Namespaced category should be set');
        $this->assertEquals('tests', $this->namespacedElement['atom:category'], 'Namespaced category should be tests');

        $this->namespacedElement['xmldata:dt'] = 'dateTime.rfc1123';

        // Changing an existing index.
        $oldEntry = $this->element['version'];
        $this->element['version'] = '1.1';
        $this->assertTrue($oldEntry != $this->element['version'], 'Version should have changed');
    }

    public function testOffsetUnset(): void
    {
        $element = new Element(file_get_contents(__DIR__ . '/fixtures/Sample.xml'));
        $namespacedElement = new Element(file_get_contents(__DIR__ . '/fixtures/NamespacedSample.xml'));

        $this->assertTrue(isset($element['version']));
        unset($element['version']);
        $this->assertFalse(isset($element['version']), 'Version should be unset');
        $this->assertEquals('', $element['version'], 'Version should be equal to the empty string');

        $this->assertTrue(isset($namespacedElement['version']));
        unset($namespacedElement['version']);
        $this->assertFalse(isset($namespacedElement['version']), 'Version should be unset');
        $this->assertEquals('', $namespacedElement['version'], 'Version should be equal to the empty string');

        $namespacedElement['atom:category'] = 'tests';
        $this->assertTrue(isset($namespacedElement['atom:category']), 'Namespaced Category should be set');
        unset($namespacedElement['atom:category']);
        $this->assertFalse(isset($namespacedElement['atom:category']), 'Category should be unset');
        $this->assertEquals('', $namespacedElement['atom:category'], 'Category should be equal to the empty string');
    }

    public function testGet(): void
    {
        $this->assertEquals('Atom Example', (string) $this->element->title);
        $this->assertEquals('Atom Example', (string) $this->namespacedElement->title);
        $this->assertEquals('4', (string) $this->element->totalResults);
        $this->assertEquals('4', (string) $this->namespacedElement->totalResults);
    }

    public function testSet(): void
    {
        $this->element->category = 'tests';
        $this->assertTrue(isset($this->element->category), 'Category should be set');
        $this->assertEquals('tests', (string) $this->element->category, 'Category should be tests');
    }

    public function testUnset(): void
    {
        $element = new Element(file_get_contents(__DIR__ . '/fixtures/Sample.xml'));
        $namespacedElement = new Element(file_get_contents(__DIR__ . '/fixtures/NamespacedSample.xml'));

        $this->assertTrue(isset($element->title));
        unset($element->title);
        $this->assertFalse(isset($element->title));

        $this->assertTrue(isset($namespacedElement->title));
        unset($namespacedElement->title);
        $this->assertFalse(isset($namespacedElement->title));
    }

    public function testIsInitialized(): void
    {
        $e = new Element('<element />');

        $e->author->name['last'] = 'Lastname';
        $e->author->name['first'] = 'Firstname';
        $e->author->name->{'Firstname:url'} = 'www.example.com';

        $e->author->title['foo'] = 'bar';
        if ($e->pants()) {
            $this->fail('<pants> does not exist, it should not have a true value');
            // This should not create an element in the actual tree.
        }
        if ($e->pants()) {
            $this->fail('<pants> should not have been created by testing for it');
            // This should not create an element in the actual tree.
        }

        $xml = $e->saveXML();

        $this->assertFalse(str_contains($xml, 'pants'), '<pants> should not be in the xml output');
        $this->assertTrue(str_contains($xml, 'www.example.com'), 'the url attribute should be set');
    }

    public function testStrings(): void
    {
        $xml = "<entry>
    <title> Using C++ Intrinsic Functions for Pipelined Text Processing</title>
    <id>http://www.oreillynet.com/pub/wlg/8356</id>
    <link rel='alternate' href='http://www.oreillynet.com/pub/wlg/8356'/>
    <summary type='xhtml'>
    <div xmlns='http://www.w3.org/1999/xhtml'>
    A good C++ programming technique that has almost no published material available on the WWW relates to using the special pipeline instructions in modern CPUs for faster text processing. Here's example code using C++ intrinsic functions to give a fourfold speed increase for a UTF-8 to UTF-16 converter compared to the original C/C++ code.
    </div>
    </summary>
    <author><name>Rick Jelliffe</name></author>
    <updated>2005-11-07T08:15:57-08:00</updated>
</entry>";
        $element = new Element($xml);

        $this->assertInstanceOf(Element::class, $element->summary, '__get access should return an Element instance');
        $this->assertFalse($element->summary() instanceof Element, 'method access should not return an Element instance');
        $this->assertTrue(is_string($element->summary()), 'method access should return a string');
        $this->assertFalse(is_string($element->summary), '__get access should not return a string');
    }

    public function testAppendChild(): void
    {
        $e = new Element('<element />');
        $e2 = new Element('<child />');

        $e->appendChild($e2);
        $this->assertEquals('<element><child/></element>', $e->saveXmlFragment());
    }

    public function testFromArray(): void
    {
        $e = new Element('<element />');

        $e->fromArray([
            'user' => 'Joe Schmoe',
            'atom:title' => 'Test Title',
            'child#href' => 'http://www.example.com/',
            '#title' => 'Test Element',
        ]);

        $this->assertEquals(
            '<element title="Test Element"><user>Joe Schmoe</user><atom:title xmlns:atom="http://www.w3.org/2005/Atom">Test Title</atom:title><child href="http://www.example.com/"/></element>',
            $e->saveXmlFragment()
        );

        $e = new Element('<element />');
        $e->fromArray(['author' => ['name' => 'Joe', 'email' => 'joe@example.com']]);

        $this->assertEquals(
            '<element><author><name>Joe</name><email>joe@example.com</email></author></element>',
            $e->saveXmlFragment()
        );
    }

    public function testIllegalFromArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $e = new Element('<element />');
        $e->fromArray(['#name' => ['foo' => 'bar']]);
    }

    public function testCustomGetterGet(): void
    {
        $xml = '<element><author><name>Joe</name><email>joe@example.com</email></author></element>';
        $e = new ElementTestCustomGetter($xml);

        $this->assertEquals((string) $e->author, (string) $e->writer);
        $this->assertEquals((string) $e->author->name, (string) $e->psuedonym);
    }

    public function testCustomGetterCall(): void
    {
        $xml = '<element><author><name>Joe</name><email>joe@example.com</email></author></element>';
        $e = new ElementTestCustomGetter($xml);

        $this->assertEquals($e->author(), $e->writer());
        $this->assertEquals($e->author->name(), $e->psuedonym());
    }
}

/**
 * Test helper class with custom getters.
 */
class ElementTestCustomGetter extends Element
{
    public function getWriter(): mixed
    {
        return $this->author;
    }

    public function getPsuedonym(): mixed
    {
        return $this->author->name;
    }
}

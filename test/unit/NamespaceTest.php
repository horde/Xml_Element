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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Element::class)]
class NamespaceTest extends TestCase
{
    public function testLookupPredefinedNamespace(): void
    {
        $this->assertEquals('http://www.w3.org/2005/Atom', Element::lookupNamespace('atom'));
        $this->assertEquals('http://a9.com/-/spec/opensearchrss/1.0/', Element::lookupNamespace('opensearch'));
        $this->assertEquals('http://blogs.law.harvard.edu/tech/rss', Element::lookupNamespace('rss'));
        $this->assertEquals('http://www.w3.org/1999/02/22-rdf-syntax-ns', Element::lookupNamespace('rdf'));
    }

    public function testLookupUnregisteredReturnsPrefix(): void
    {
        $this->assertEquals('custom', Element::lookupNamespace('custom'));
        $this->assertEquals('unknown:prefix', Element::lookupNamespace('unknown:prefix'));
    }

    public function testRegisterCustomNamespace(): void
    {
        Element::registerNamespace('custom', 'http://example.com/custom');
        $this->assertEquals('http://example.com/custom', Element::lookupNamespace('custom'));
    }

    public function testNamespacedAttributeAccess(): void
    {
        $xml = file_get_contents(__DIR__ . '/fixtures/NamespacedSample.xml');
        $element = new Element($xml);

        $element['atom:category'] = 'test-category';
        $this->assertTrue(isset($element['atom:category']));
        $this->assertEquals('test-category', $element['atom:category']);

        unset($element['atom:category']);
        $this->assertFalse(isset($element['atom:category']));
    }

    public function testNamespacedElementCreation(): void
    {
        $element = new Element('<root />');
        $element->{'atom:title'} = 'Test Title';

        $xml = $element->saveXmlFragment();
        $this->assertStringContainsString('atom:title', $xml);
        $this->assertStringContainsString('xmlns:atom', $xml);
        $this->assertStringContainsString('http://www.w3.org/2005/Atom', $xml);
        $this->assertStringContainsString('Test Title', $xml);
    }
}

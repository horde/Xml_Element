<?php

declare(strict_types=1);

/**
 * Portions Copyright 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * Copyright 2007-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Xml
 */

namespace Horde\Xml\Element;

use ArrayAccess;
use DOMDocument;
use DOMElement;
use Horde\Util\HordeString;
use Horde\Xml\Element\ElementInterface;
use InvalidArgumentException;

/**
 * Wraps a DOMElement allowing for SimpleXML-like access to attributes.
 *
 * @method mixed TAGNAME() To get the un-wrapped value of a node, use
 * method syntax ($xml_element->tagname()). This will return the
 * string value of the tag if it is a single tag, an array of
 * Element objects if there are multiple tags, or null if
 * the tag does not exist.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2007-2026 The Horde Project
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Xml
 */
class Element implements ElementInterface, ArrayAccess
{
    /**
     * Registered XML namespaces.
     */
    protected static array $namespaces = [
        'opensearch' => 'http://a9.com/-/spec/opensearchrss/1.0/',
        'atom' => 'http://www.w3.org/2005/Atom',
        'rss' => 'http://blogs.law.harvard.edu/tech/rss',
        'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns',
    ];

    /**
     * The wrapped DOM element.
     */
    protected DOMElement|Element|string $element;

    /**
     * A string representation of the element, used when serializing/unserializing.
     */
    protected ?string $serialized = null;

    /**
     * Parent element reference.
     */
    protected ?Element $parentElement = null;

    /**
     * Cached child nodes.
     */
    protected ?array $children = null;

    /**
     * Whether this element has been appended to its parent.
     */
    protected bool $appended = true;

    /**
     * Get the full version of a namespace prefix.
     *
     * Looks up a prefix (atom:, etc.) in the list of registered
     * namespaces and returns the full namespace URI if
     * available. Returns the prefix, unmodified, if it's not
     * registered.
     */
    public static function lookupNamespace(string $prefix): string
    {
        return self::$namespaces[$prefix] ?? $prefix;
    }

    /**
     * Add a namespace and prefix to the registered list.
     *
     * Takes a prefix and a full namespace URI and adds them to the
     * list of registered namespaces for use by lookupNamespace().
     */
    public static function registerNamespace(string $prefix, string $namespaceUri): void
    {
        self::$namespaces[$prefix] = $namespaceUri;
    }

    /**
     * Element constructor.
     *
     * @param DOMElement|Element|string $element  The DOM element,
     * pre-existing Element, or XML string that we're encapsulating.
     */
    public function __construct(DOMElement|Element|string $element)
    {
        $this->element = $element;
        $this->__wakeup();
    }

    /**
     * Get a DOM representation of the element.
     *
     * Returns the underlying DOM object, which can then be manipulated with
     * full DOM methods.
     */
    public function getDom(): DOMElement
    {
        return $this->element;
    }

    /**
     * Update the object from a DOM element.
     *
     * Take a DOMElement object, which may be originally from a call
     * to getDom() or may be custom created, and use it as the
     * DOM tree for this Element.
     */
    public function setDom(DOMElement $element): void
    {
        $this->element = $this->element->ownerDocument->importNode($element, true);
    }

    /**
     * Add child elements and attributes to this element from a simple
     * key => value hash. Keys can be:
     *
     *   ElementName               -> <$ElementName> will be appended with
     *                                a value of $value
     *   #AttributeName            -> An attribute $AttributeName will be
     *                                added to this element with a value
     *                                of $value
     *   ElementName#AttributeName -> <$ElementName> will be appended to this
     *                                element if it doesn't already exist,
     *                                and have its attribute $AttributeName
     *                                set to $value
     *
     * @param array $array Hash to import into this element.
     */
    public function fromArray(array $array): void
    {
        foreach ($array as $key => $value) {
            $element = null;
            $attribute = null;

            $hash_position = strpos($key, '#');
            if ($hash_position === false) {
                $element = $key;
            } elseif ($hash_position === 0) {
                $attribute = substr($key, 1);
            } else {
                list($element, $attribute) = explode('#', $key, 2);
            }

            if ($element !== null) {
                if ($attribute !== null) {
                    $this->{$element}[$attribute] = $value;
                } else {
                    if (is_array($value)) {
                        // Detect numeric arrays and treat them as multiple
                        // instances of the same key.
                        $firstKey = key($value);
                        if ($firstKey === 0) {
                            if (strpos($element, ':') !== false) {
                                list($ns) = explode(':', $element, 2);
                                $baseNode = $this->element->ownerDocument->createElementNS(
                                    self::lookupNamespace($ns),
                                    $element
                                );
                            } else {
                                $baseNode = $this->element->ownerDocument->createElement($element);
                            }

                            foreach ($value as $v) {
                                $node = $baseNode->cloneNode();
                                if (is_array($v)) {
                                    $e = new static($node);
                                    $e->fromArray($v);
                                } else {
                                    $node->nodeValue = $v;
                                    $e = new static($node);
                                }
                                $this->appendChild($e);
                            }
                        } else {
                            $this->$element->fromArray($value);
                        }
                    } else {
                        $this->$element = $value;
                    }
                }
            } elseif ($attribute !== null) {
                $this[$attribute] = $value;
            }
        }
    }

    /**
     * Append a child node to this element.
     */
    public function appendChild(ElementInterface $element): void
    {
        if ($element instanceof Element) {
            $element->setParent($this);
            $element->ensureAppended();
            $this->expireCachedChildren();
        }
    }

    /**
     * Get an XML string representation of this element.
     *
     * Returns a string of this element's XML, including the XML
     * prologue.
     */
    public function saveXml(bool $formatted = false): string
    {
        // Return a complete document including XML prologue.
        $doc = new DOMDocument(
            $this->element->ownerDocument->version ?? '1.0',
            $this->element->ownerDocument->encoding ?? 'UTF-8'
        );
        $doc->formatOutput = $formatted;
        $doc->appendChild($doc->importNode($this->element, true));
        return $doc->saveXML();
    }

    /**
     * Get the XML for only this element.
     *
     * Returns a string of this element's XML without prologue.
     */
    public function saveXmlFragment(bool $formatted = false): string
    {
        $oldFormatted = $this->element->ownerDocument->formatOutput;
        $this->element->ownerDocument->formatOutput = $formatted;
        $xml = $this->element->ownerDocument->saveXML($this->element);
        $this->element->ownerDocument->formatOutput = $oldFormatted;
        return $xml;
    }

    /**
     * Unserialization handler; handles $this->element being an instance of
     * DOMElement or Element, or parses it as an XML string.
     */
    public function __wakeup(): void
    {
        if (isset($this->element) && $this->element instanceof DOMElement) {
            return;
        }

        if (isset($this->element) && $this->element instanceof Element) {
            $this->element = $this->element->getDom();
            return;
        }

        if ($this->serialized) {
            $this->element = $this->serialized;
            $this->serialized = null;
        }

        if (isset($this->element) && is_string($this->element)) {
            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;

            $extract = false;
            if (!str_starts_with($this->element, '<?xml')) {
                $extract = true;
                $preamble = '<?xml version="1.0" encoding="UTF-8" ?><root ';
                foreach (self::$namespaces as $prefix => $nsUri) {
                    $preamble .= " xmlns:$prefix=\"$nsUri\"";
                }
                $preamble .= '>';
                $this->element = $preamble . $this->element . '</root>';
            }

            $loaded = @$doc->loadXML($this->element);
            if (!$loaded) {
                $lastError = error_get_last();
                throw new Exception(
                    'DOMDocument cannot parse XML: ' . ($lastError['message'] ?? 'Unknown error')
                );
            }

            if ($extract) {
                $newDoc = new DOMDocument();
                $this->element = $newDoc->importNode($doc->documentElement->childNodes->item(0), true);
            } else {
                $this->element = $doc->documentElement;
            }

            return;
        }

        throw new InvalidArgumentException(
            'Element initialization value must be a DOMElement, an Element, or a non-empty string; '
            . (isset($this->element) && is_object($this->element) ? get_class($this->element) : (isset($this->element) ? gettype($this->element) : 'uninitialized'))
            . ' given'
        );
    }

    /**
     * Prepare for serialization.
     *
     * @return array<string>
     */
    public function __sleep(): array
    {
        $this->serialized = $this->saveXml();
        return ['serialized'];
    }

    /**
     * Map variable access onto the underlying entry representation.
     *
     * Get-style access returns an Element representing the
     * child element accessed. To get string values, use method syntax
     * with the __call() overriding.
     *
     * @param string $var The property to access.
     * @return Element|array<Element>|null
     */
    public function __get(string $var): mixed
    {
        $nodes = $this->children($var);
        $length = count($nodes);

        if ($length === 1) {
            if ($nodes[0] instanceof Element) {
                return $nodes[0];
            }
            return new static($nodes[0]);
        } elseif ($length > 1) {
            if ($nodes[0] instanceof Element) {
                return $nodes;
            }
            return array_map(fn($e) => new static($e), $nodes);
        } else {
            // When creating anonymous nodes for __set chaining, don't
            // call appendChild() on them. Instead we pass the current
            // element to them as an extra reference; the child is
            // then responsible for appending itself when it is
            // actually set. This way "if ($foo->bar)" doesn't create
            // a phantom "bar" element in our tree.
            if (strpos($var, ':') !== false) {
                list($ns, $elt) = explode(':', $var, 2);
                $node = $this->element->ownerDocument->createElementNS(
                    self::lookupNamespace($ns),
                    $elt
                );
            } else {
                $node = $this->element->ownerDocument->createElement($var);
            }
            $node = new static($node);
            $node->setParent($this);
            return $node;
        }
    }

    /**
     * Map variable sets onto the underlying entry representation.
     *
     * @param string $var The property to change.
     * @param scalar $val The property's new value.
     */
    public function __set(string $var, mixed $val): void
    {
        if (!is_scalar($val)) {
            throw new InvalidArgumentException(
                'Element values must be scalars, ' . gettype($val) . ' given'
            );
        }

        $this->ensureAppended();

        $nodes = $this->children($var);
        if (!$nodes) {
            if (strpos($var, ':') !== false) {
                list($ns) = explode(':', $var, 2);
                $node = $this->element->ownerDocument->createElementNS(
                    self::lookupNamespace($ns),
                    $var,
                    (string) $val
                );
                $this->element->appendChild($node);
            } else {
                $node = $this->element->ownerDocument->createElement($var, (string) $val);
                $this->element->appendChild($node);
            }

            $this->expireCachedChildren();
        } elseif (count($nodes) > 1) {
            throw new Exception('Cannot set the value of multiple nodes simultaneously.');
        } else {
            $nodes[0]->nodeValue = (string) $val;
        }
    }

    /**
     * Map isset calls onto the underlying entry representation.
     */
    public function __isset(string $var): bool
    {
        return (bool) $this->children($var);
    }

    /**
     * Get the value of an element with method syntax.
     *
     * Map method calls to get the string value of the requested
     * element. If there are multiple elements that match, this will
     * return an array of those objects.
     *
     * @param string $var The element to get the string value of.
     * @param array $unused Unused parameters.
     *
     * @return string|array<Element>|null The node's value, null, or an array of nodes.
     */
    public function __call(string $var, array $unused): mixed
    {
        $nodes = $this->children($var);

        if (!$nodes) {
            return null;
        } elseif (count($nodes) > 1) {
            if ($nodes[0] instanceof Element) {
                return $nodes;
            }
            return array_map(
                fn($e) => new static($e),
                $nodes
            );
        } else {
            if ($nodes[0] instanceof Element) {
                return (string) $nodes[0];
            } else {
                return $nodes[0]->nodeValue;
            }
        }
    }

    /**
     * Remove all children matching $var.
     */
    public function __unset(string $var): void
    {
        $nodes = $this->children($var);
        foreach ($nodes as $node) {
            $parent = $node->parentNode;
            $parent->removeChild($node);
        }

        $this->expireCachedChildren();
    }

    /**
     * Returns the nodeValue of this element when this object is used
     * in a string context.
     */
    public function __toString(): string
    {
        return $this->element->nodeValue;
    }

    /**
     * Set the parent element of this object to another Element.
     */
    public function setParent(Element $element): void
    {
        $this->parentElement = $element;
        $this->appended = false;
    }

    /**
     * Appends this element to its parent if necessary.
     */
    protected function ensureAppended(): void
    {
        if (!$this->appended) {
            $parentDom = $this->parentElement->getDom();
            if (!$parentDom->ownerDocument->isSameNode($this->element->ownerDocument)) {
                $this->element = $parentDom->ownerDocument->importNode($this->element, true);
            }

            $parentDom->appendChild($this->element);
            $this->appended = true;
            $this->parentElement->ensureAppended();
        }
    }

    /**
     * Finds children with tagnames matching $var.
     *
     * Similar to SimpleXML's children() method.
     *
     * @param string $var Tagname to match, can be either namespace:tagName or just tagName.
     * @return array<DOMElement|Element>
     */
    protected function children(string $var): array
    {
        if ($this->children === null) {
            $this->cacheChildren();
        }

        // Honor any explicit getters. Because Element has a __call()
        // method, is_callable returns true on every method name. Use
        // method_exists instead.
        $varMethod = 'get' . HordeString::ucfirst($var);
        if (method_exists($this, $varMethod)) {
            $children = call_user_func([$this, $varMethod]);
            if ($children === null) {
                $this->children[$var] = [];
            } elseif (!is_array($children)) {
                $this->children[$var] = [$children];
            } else {
                $this->children[$var] = $children;
            }
        }

        return $this->children[$var] ?? [];
    }

    /**
     * Build a cache of child nodes.
     */
    protected function cacheChildren(): void
    {
        $this->children = [];
        foreach ($this->element->childNodes as $child) {
            if (!isset($this->children[$child->localName])) {
                $this->children[$child->localName] = [];
            }
            $this->children[$child->localName][] = $child;

            if ($child->prefix) {
                $prefixedName = $child->prefix . ':' . $child->localName;
                if (!isset($this->children[$prefixedName])) {
                    $this->children[$prefixedName] = [];
                }
                $this->children[$prefixedName][] = $child;
            }
        }
    }

    /**
     * Expire cached children.
     */
    protected function expireCachedChildren(): void
    {
        $this->children = null;
    }

    /**
     * Required by the ArrayAccess interface.
     */
    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset)) {
            return false;
        }

        if (strpos($offset, ':') !== false) {
            list($ns, $attr) = explode(':', $offset, 2);
            return $this->element->hasAttributeNS(self::lookupNamespace($ns), $attr);
        } else {
            return $this->element->hasAttribute($offset);
        }
    }

    /**
     * Required by the ArrayAccess interface.
     */
    public function offsetGet(mixed $offset): ?string
    {
        if (!is_string($offset)) {
            throw new InvalidArgumentException('Attribute name must be string');
        }

        if (strpos($offset, ':') !== false) {
            list($ns, $attr) = explode(':', $offset, 2);
            return $this->element->getAttributeNS(self::lookupNamespace($ns), $attr) ?: null;
        } else {
            return $this->element->getAttribute($offset) ?: null;
        }
    }

    /**
     * Required by the ArrayAccess interface.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            throw new InvalidArgumentException('Attribute name must be string');
        }

        if (!is_scalar($value)) {
            throw new InvalidArgumentException(
                'Element values must be scalars, ' . gettype($value) . ' given'
            );
        }

        $this->ensureAppended();

        if (strpos($offset, ':') !== false) {
            list($ns) = explode(':', $offset, 2);
            $this->element->setAttributeNS(self::lookupNamespace($ns), $offset, (string) $value);
        } else {
            $this->element->setAttribute($offset, (string) $value);
        }

        $this->expireCachedChildren();
    }

    /**
     * Required by the ArrayAccess interface.
     */
    public function offsetUnset(mixed $offset): void
    {
        if (!is_string($offset)) {
            return;
        }

        if (strpos($offset, ':') !== false) {
            list($ns, $attr) = explode(':', $offset, 2);
            $this->element->removeAttributeNS(self::lookupNamespace($ns), $attr);
        } else {
            $this->element->removeAttribute($offset);
        }

        $this->expireCachedChildren();
    }
}

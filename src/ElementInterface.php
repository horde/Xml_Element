<?php

declare(strict_types=1);

/**
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
use DOMElement;

/**
 * Interface for XML element wrappers.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @copyright 2007-2026 The Horde Project
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Xml
 */
interface ElementInterface extends ArrayAccess
{
    /**
     * Get the underlying DOM element.
     */
    public function getDom(): DOMElement;

    /**
     * Update the object from a DOM element.
     */
    public function setDom(DOMElement $element): void;

    /**
     * Get an XML string representation with prologue.
     */
    public function saveXml(bool $formatted = false): string;

    /**
     * Get the XML for only this element without prologue.
     */
    public function saveXmlFragment(bool $formatted = false): string;

    /**
     * Add child elements and attributes from a key => value hash.
     */
    public function fromArray(array $array): void;

    /**
     * Append a child node to this element.
     */
    public function appendChild(ElementInterface $element): void;
}

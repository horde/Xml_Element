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

use Countable;
use DOMElement;
use Iterator;

/**
 * Interface for XML element list iterators.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @copyright 2007-2026 The Horde Project
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Xml
 */
interface ElementListInterface extends ElementInterface, Countable, Iterator
{
    /**
     * Build the cache of list items for iteration.
     *
     * @return array<int, DOMElement> Array of DOM elements to iterate over
     */
    public function buildListItemCache(): array;
}

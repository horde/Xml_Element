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
use Horde\Xml\Element\Element;
use Horde\Xml\Element\ElementListInterface;
use Iterator;

/**
 * Abstract base class for iterating over XML element collections.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @copyright 2007-2026 The Horde Project
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Xml
 */
abstract class ElementList extends Element implements ElementListInterface, Countable, Iterator
{
    /**
     * Cache of list items.
     *
     * @var array<int, DOMElement>|null
     */
    protected ?array $listItems = null;

    /**
     * Current index on the collection of list items for the Iterator
     * implementation.
     */
    protected int $listItemIndex = 0;

    /**
     * The classname for individual list items.
     */
    protected string $listItemClassName = Element::class;

    /**
     * Ensure that listItems is populated by calling the concrete implementation's
     * buildListItemCache() method.
     */
    public function __wakeup(): void
    {
        parent::__wakeup();

        $this->listItems = $this->buildListItemCache();
    }

    /**
     * Called by __wakeup to cache list items. Must be implemented in the
     * extending class to return the array of list items.
     *
     * @return array<int, DOMElement> Array of DOM elements to iterate over
     */
    abstract public function buildListItemCache(): array;

    /**
     * Get the number of items in this list.
     *
     * @return int Item count.
     */
    public function count(): int
    {
        return count($this->listItems ?? []);
    }

    /**
     * Required by the Iterator interface.
     *
     * @internal
     */
    public function rewind(): void
    {
        $this->listItemIndex = 0;
    }

    /**
     * Required by the Iterator interface.
     *
     * @internal
     *
     * @return mixed The current row, or null if no rows.
     */
    public function current(): mixed
    {
        return new ($this->listItemClassName)(
            $this->listItems[$this->listItemIndex]
        );
    }

    /**
     * Required by the Iterator interface.
     *
     * @internal
     *
     * @return int The current row number (starts at 0).
     */
    public function key(): int
    {
        return $this->listItemIndex;
    }

    /**
     * Required by the Iterator interface.
     *
     * @internal
     */
    public function next(): void
    {
        ++$this->listItemIndex;
    }

    /**
     * Required by the Iterator interface.
     *
     * @internal
     *
     * @return bool Whether the iteration is valid
     */
    public function valid(): bool
    {
        return $this->listItemIndex >= 0
            && $this->listItemIndex < count($this->listItems ?? []);
    }
}

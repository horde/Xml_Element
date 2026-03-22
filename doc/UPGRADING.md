# Upgrading to Modern API (src/)

## Overview

Xml_Element 3.0 introduces a modern PSR-4 implementation alongside the existing PSR-0 API. Both APIs are fully compatible and will coexist until 4.0.0.

## Old API (lib/)

```php
// PSR-0 class names with underscores
$element = new Horde_Xml_Element('<root><child>value</child></root>');
echo $element->child(); // "value"

// Abstract classes use underscore-prefixed methods
class MyList extends Horde_Xml_Element_List
{
    protected function _buildListItemCache()
    {
        return $results;
    }
}
```

## New API (src/)

```php
use Horde\Xml\Element\Element;
use Horde\Xml\Element\ElementList;

// PSR-4 namespaced classes
$element = new Element('<root><child>value</child></root>');
echo $element->child(); // "value"

// Abstract classes use public methods without underscores
class MyList extends ElementList
{
    public function buildListItemCache(): array
    {
        return $results;
    }
}
```

## Key Differences

### Namespace and Class Names

| Old (lib/)                      | New (src/)                        |
|---------------------------------|-----------------------------------|
| `Horde_Xml_Element`             | `Horde\Xml\Element\Element`       |
| `Horde_Xml_Element_List`        | `Horde\Xml\Element\ElementList`   |
| `Horde_Xml_Element_Exception`   | `Horde\Xml\Element\Exception`     |

### Method Names (Internal Methods Only)

**For subclasses of ElementList:**

| Old (lib/)                   | New (src/)                  |
|------------------------------|----------------------------|
| `_buildListItemCache()`      | `buildListItemCache()`     |

**Note:** These are the ONLY method names that changed. All public API methods (`__get()`, `__set()`, `saveXml()`, `fromArray()`, etc.) remain unchanged.

### Property Names (Internal Only)

Internal properties no longer use underscore prefixes (you typically don't access these directly):

| Old (lib/)          | New (src/)         |
|---------------------|--------------------|
| `_element`          | `element`          |
| `_serialized`       | `serialized`       |
| `_parentElement`    | `parentElement`    |
| `_children`         | `children`         |
| `_appended`         | `appended`         |
| `_listItems`        | `listItems`        |
| `_listItemIndex`    | `listItemIndex`    |
| `_listItemClassName`| `listItemClassName`|

### Type Declarations

The new API includes explicit type hints on all methods and properties:

```php
// Old (lib/) - no types
public function saveXml($formatted = false)
{
    return $xml;
}

// New (src/) - explicit types
public function saveXml(bool $formatted = false): string
{
    return $xml;
}
```

### Exception Handling

```php
// Old (lib/)
use Horde_Xml_Element_Exception;
try {
    $element = new Horde_Xml_Element($xml);
} catch (Horde_Xml_Element_Exception $e) {
    // handle error
}

// New (src/)
use Horde\Xml\Element\Element;
use Horde\Xml\Element\Exception;
try {
    $element = new Element($xml);
} catch (Exception $e) {
    // handle error
}
```

### Interfaces

The new API provides interfaces for type hinting:

```php
use Horde\Xml\Element\ElementInterface;
use Horde\Xml\Element\ElementListInterface;

function processElement(ElementInterface $element): void
{
    // Type-safe element processing
}

function processList(ElementListInterface $list): void
{
    // Type-safe list iteration
}
```

## Migration Path

### Simple Migration (No Subclasses)

If you only use the main Element class without subclassing:

1. Add `use Horde\Xml\Element\Element;` at the top of your file
2. Replace `new Horde_Xml_Element(...)` with `new Element(...)`
3. Update exception handling to catch `Horde\Xml\Element\Exception`

That's it! The API is otherwise identical.

### Advanced Migration (Subclassing ElementList)

If you subclass ElementList, you need to update method names:

**Before:**
```php
class MyFeedList extends Horde_Xml_Element_List
{
    protected function _buildListItemCache()
    {
        $results = [];
        foreach ($this->_element->childNodes as $child) {
            if ($child->localName === 'item') {
                $results[] = $child;
            }
        }
        return $results;
    }
}
```

**After:**
```php
use Horde\Xml\Element\ElementList;

class MyFeedList extends ElementList
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
```

Hint: Think twice before overloading buildListItemCache. It's probably better though of as an implementation detail.

**Changes:**
1. `_buildListItemCache()` → `buildListItemCache()` (no underscore, public visibility)
2. `$this->_element` → `$this->element` (no underscore)
3. Add return type hint: `: array`

## Backward Compatibility

The lib/ classes remain unchanged and fully functional:

- `Horde_Xml_Element` - Original PSR-0 implementation (unchanged)
- `Horde_Xml_Element_List` - Original PSR-0 implementation (unchanged)
- `Horde_Xml_Element_Exception` - Original PSR-0 implementation (unchanged)

Both APIs work side by side:

```php
// This works
$oldElement = new Horde_Xml_Element('<root/>');
$newElement = new Horde\Xml\Element\Element('<child/>');
// But note: mixing APIs means you need to be extra careful with type compatibility
```

## Why Upgrade?

1. **Type Safety:** Full type hints catch errors at development time
2. **Modern Standards:** PSR-4 autoloading, PER-1 coding style
3. **Better IDE Support:** Type hints enable autocomplete and static analysis
4. **Future-Proof:** lib/ will be removed in 4.0.0
5. **Interfaces:** Use ElementInterface/ElementListInterface for better type hinting

## Timeline

- **3.0.0:** Both APIs available (lib/ and src/)
- **3.x:** lib/ maintained for backward compatibility
- **4.0.0:** lib/ removed, src/ only

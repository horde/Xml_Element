<?php
/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Xml_Element
 * @subpackage UnitTests
 */
namespace Horde\Xml\Element;
use \PHPUnit\Framework\TestCase;
use \Horde_Xml_Element_List;

class CountTest extends TestCase
{
    public function testCount()
    {
        $l = new Horde_CountTest_List(
            '<?xml version="1.0" encoding="utf-8"?><list><item>1</item><item>2</item></list>'
        );
        $this->assertEquals(2, $l->count(), 'List count should be 2');
    }

}

class Horde_CountTest_List extends Horde_Xml_Element_List
{
    protected function _buildListItemCache()
    {
        $results = array();
        foreach ($this->_element->childNodes as $child) {
            if ($child->localName == 'item') {
                $results[] = $child;
            }
        }

        return $results;
    }

}
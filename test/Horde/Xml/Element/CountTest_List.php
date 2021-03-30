<?php
/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Xml_Element
 * @subpackage UnitTests
 */
namespace Horde\Xml\Element;
use \Horde_Xml_Element_List;

class CountTest_List extends Horde_Xml_Element_List
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

<?php
# Copyright (c) 2002 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once "container/iterator.class.php";

/**
 * Iterator for array containers.
 *
 * For method details please see the iterator superclass.
 *
 * @access public
 * @package Containers
 */
class iterator_array extends iterator {
    var $_key; # Current position in list.

    function iterator_array (&$container, $key)
    {
        iterator::iterator ($container);
        $this->_key = $key;
    }

    function &current ()
    {
        $ct =& $this->_ct;
        $array =& $_ct->_array;
        $keys =& $_ct->_keys;

        return $array[$this->_key]];
    }

    function advance ($distance = 1)
    {
        $ct =& $this->_ct;
        $array =& $_ct->_array;
        $keys =& $_ct->_keys;
        $size = sizeof ($array);

        $pos = array_search ($keys[$this->_key]) + $distance;
        if ($pos < 0) {
            $pos = 0;
            return false;
        }
        if ($pos > $size) {
            $pos = $size;
            return false;
        }
        $this->_pos = $pos;
        return true;
    }
}
?>

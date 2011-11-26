<?php
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once 'cursor/cursor.class.php';

/**
 * Cursor for php arrays.
 *
 * @access public
 * @package Cursor interfaces
 */
class cursor_php_array extends cursor {
    var $_array;

    # Perform query and read the first row.
    function &_query ($dummy = '')
    {
        $field = $this->_source;

        # Fetch array field from lower cursor.
        $rec = $this->lower->current ();
        $this->_array = unserialize ($rec[$field]);
        $array =& $this->_array;

        if (!is_array ($array) || !sizeof ($array))
            return false;

        $this->_current =& reset ($array);

        return true;
    }

    # Return next record.
    function &_get ()
    {
        $array =& $this->_array;

        $ret = $this->_current;
        $this->key = key ($array);
        $this->_current =& next ($array);

        return $ret;
    }

    # Update a row's field.
    function set (&$value)
    {
        $this->_fetch_array ();
        $this->_array[$this->_key][$this->_field] = $value;
        $this->_writeback ();
    }

    function _fetch_array ()
    {
        $this->_array = unserialize ($this->lower->current ());
    }

    function _writeback ()
    {
        $this->_lower->set (addslashes (serialize ($this->_array)));
    }

    function create ($preset_values)
    {
        if (!$preset_values)
            $prset_valuese = array ();

        $this->_fetch_array ();
        $this->_array[] = $preset_values;
        $this->_writeback ();

        return true;
    }

    function delete (&$app, $source, $key, &$l)
    {
        $this->_fetch_array ($app, $l);
        unset ($this->_array[$key]);
        $this->_writeback ($app, $l);
    }
}
?>

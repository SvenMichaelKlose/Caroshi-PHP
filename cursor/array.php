<?php
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once PATH_TO_CAROSHI . '/cursor/cursor.class.php';

/**
 * Cursor for php arrays.
 *
 * @access public
 * @package Cursor interfaces
 */
class cursor_array extends cursor {
    var $_array;

    # Perform query and read the first row.
    function _query ($dummy_selection, $dummy_order)
    {
        $field = $this->_source;

        if ($this->_array = $this->_source->current ())
            return reset ($array);
    }

    # Return next record.
    function _get ()
    {
        $this->key = key ($this->_array);
        return next ($array);
    }

    # Update a row's field.
    function set (&$values)
    {
        $this->_source->set ($values);
    }

    function create ($values = 0)
    {
        return $this->_source->create ($values);
    }

    function delete ()
    {
        return $this->_source->delete ();
    }
}
?>

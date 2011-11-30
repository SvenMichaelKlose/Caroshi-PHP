<?php
# Copyright (c) 2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once PATH_TO_CARISHI . '/cursor/cursor.class.php';

/**
 * Cursor that makes an array of cursors behave like one.
 *
 * The last record/cursor that was get()'ed is referred to as the
 * 'current' cursor.
 *
 * @access public
 * @package Cursor interfaces
 */
class cursor_merged extends cursor {
    # Private. Hands off.
    var $_cursors; # Set of cursors.
    var $_index;   # Curret index in set.
    var $_size;    # Number of entries in result set.

    /**
     * Construct cursor.
     *
     * @access public
     */
    function cursor_merged ()
    {
        $this->cursor ('merged');
    }

    /**
     * Initialise with new set of cursors.
     *
     * @access private
     * @param array $cursors Cursor set.
     */
    function _query (&$cursors)
    {
        if (!is_array ($cursors))
            die_traced ('Need an array.');

        $this->_cursors =& $cursors;
        $this->_index = 0;
        $this->_size = 0;
        $i = 0;
        foreach ($cursors as $cursor) {
            if (!is_a ($cursor, 'cursor'))
                die_traced ("Index $i in array is not a cursor - stop.");
            $this->_size += $cursor->size ();
        }

        return true;
    }

    /**
     * Get reference to current cursor.
     *
     * @access private
     * @returns object cursor Reference to current cursor.
     */
    function &_pos ()
    {
        $index =& $this->_index;
        $set =& $this->_cursors;
        $s = sizeof ($set) - 1;

        if ($index > $s)
            $index = $s;

        return $set[$index];
    }

    /**
     * Get record and use it with the other functions.
     *
     * @access private
     * @return mixed Record.
     */
    function &_get ()
    {
        $c =& $this->_pos ();

        $rec =& $c->get ();
        if ($rec)
            return $rec;

        # Step to next cursor.
        $this->_index++;
        $c =& $this->_pos ();
        if (!$c)
            return;

        $rec =& $c->get ();
        if ($rec)
            return $rec;
    }

    /**
     * Update a row's field.
     *
     * @access private
     * @param mixed $value The new value of the selected field.
     */
    function set ($value)
    {
        $c =& $this->_pos ();
        $c->set ($value);
    }

    /**
     * Create a record in the current cursor.
     *
     * @access private
     * @param array $pre Preset values array keyed by field name.
     */
    function create ($pre = 0)
    {
        $c =& $this->_pos ();
        $c->create ($pre);
    }

    /**
     * Delete the record the current cursor points to.
     *
     * @access private
     */
    function delete ()
    {
        $c =& $this->_pos ();
        $c->delete ($pre);
    }

    /**
     * Get the number of records in all cursors.
     *
     * @access private
     * @returns int Number of records.
     */
    function size ()
    {
        return $this->_size;
    }

    function set_source ()
    {
        die_traced ();
    }

    /**
     * Get the current cursor's source name.
     *
     * @access private
     * @returns string Source name.
     */
    function source ()
    {
        $c =& $this->_pos ();
        if (!$c)
            return;
        return $c->source ();
    }

    function set_key ($key)
    {
        $c =& $this->_pos ();
        if (!$c)
            return;
        return $c->set_key ($key);
    }

    /**
     * Get the current cursor's source name.
     *
     * @access private
     * @returns mixed Key value of the last get()'ed record.
     */
    function key ()
    {
        $c =& $this->_pos ();
        if (!$c)
            return;
        return $c->key ();
    }

    /**
     * Select a field in the current record.
     *
     * @access private
     * @param string $field The field's name.
     */
    function set_field ($field)
    {
        $c =& $this->_pos ();
        if (!$c)
            return;
        $c->set_field ($field);
    }

    /**
     * Get the name of the currently selected field.
     *
     * @access private
     * @param string Returns an empty string if no field is selected.
     */
    function field ()
    {
        $c =& $this->_pos ();
        if (!$c)
            return;
        return $c->field ();
    }

    /**
     * Get the type of the currently used cursor.
     *
     * @access private
     * @param string Type name.
     */
    function type ()
    {
        $c =& $this->_pos ();
        if (!$c)
            return;
        return $c->type ();
    }

    /**
     * Get a unique ID string of the current record.
     *
     * @access private
     * @returns string The ID string.
     */
    function id ()
    {
        $c =& $this->_pos ();
        if (!$c)
            return;
        return $c->id ();
    }

    /**
     * Serialize the cursor including the set.
     *
     * @access private
     */
    function &__sleep ()
    {
        $elements = cursor::__sleep ();
        $elements[] = '_cursors';
        $elements[] = '_index';
        $elements[] = '_size';
        return $elements;
    }
}
?>

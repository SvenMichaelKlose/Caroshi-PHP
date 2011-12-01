<?php

# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once PATH_TO_CAROSHI . '/cursor/cursor.class.php';

$__CURSOR_SQL_INSTANCE = 0;


/**
 * SQL cursor via global dbctrl object.
 *
 * @access public
 * @package Cursor interfaces
 */
class cursor_sql extends cursor {

    function cursor_sql ()
    {
        $this->cursor ('sql');
    }

    # Set database connection for all cursor_sql instances.
    # (Static function.)
    static function set_db (&$db)
    {
        if ($GLOBALS['__CURSOR_SQL_INSTANCE'])
            die_traced ('Connection already set.');
        $GLOBALS['__CURSOR_SQL_INSTANCE'] =& $db;
    }

    # Perform query and read the first row
    function _query ($where = '', $order = '')
    {
        $db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
        $def =& $db->def;
        $table = $this->_source;
        $pri = $def->primary ($table);

        if ($is_list = $def->is_list ($table)) {
            $this->_get_next_id = 0;
            if (!$res = $db->select ('*', $table, $where . ($where ? ' AND ' : '') . $def->prev_of ($table) . '=0'))
                return;
            $row = $res->get ();
            $this->_get_next_id = $row[$pri];
        } else
            if (!$res = $db->select ('*', $table, $where, $order))
                return;

        $this->_size = $res->num_rows ();
        $this->_res = $res;
        return true;
    }

    function _get ()
    {
        $db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
        $def =& $db->def;
        $table = $this->_source;
        $pri = $def->primary ($table);

        if ($is_list = $def->is_list ($table)) {
            $key_of_next = $this->_get_next_id;
            if (!$this->_res = $db->select ('*', $table, "$pri=$key_of_next"))
                return;
        }

        $row = $this->_res ? $this->_res->get () : null;
        if ($is_list)
            $this->_get_next_id = $row[$def->next_of ($table)];

        $this->_key = $row[$pri];
        return $row;
    }

    /**
     * Update the last fetched record.
     *
     * @access public
     * @param array $values Field values keyed by their names.
     */
    function set ($values)
    {
        type_array ($values);

        $db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
        $primary = $db->def->primary ($this->_source);
        $db->update ($this->_source, sql_assignments ($values), sql_assignment ($primary, $this->_key));
    }


    /**
     * Update field in the last fetched record.
     *
     * @access public
     * @param string $field Field name.
     * @param string $value Field value.
     */
    function set_value  ($field, $value)
    {
        type_string ($field);
        type_string ($value);

        $db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
        $primary = $db->def->primary ($this->_source);
        $db->update ($this->_source, sql_assignment ($field, $value), sql_assignment ($primary, $this->_key));
    }

    function create ($pre = 0)
    {
        $db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
        return $db->append_new ($this->_source, $pre);
    }

    function delete ()
    {
        $db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
        $source = $this->_source;
        if (!$source)
            die_traced ('No source.');
        $key = addslashes ($this->_key);

        $db->multi_delete ($source, $key);
    }

    # Get number of entries in set.
    function size ()
    {
        return $this->_size;
    }

    # Serialise only the cursor.
    function &__sleep ()
    {
        $elements = cursor::__sleep ();
        $elements[] = '_size';
        return $elements;
    }

    # Private. Hands off.
    var $_res;  # db_result object.
    var $_size; # Number of entries in result set.
}
?>

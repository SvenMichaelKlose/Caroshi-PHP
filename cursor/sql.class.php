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

    var $_db;
    var $_pre = array ();
    var $_res;  # db_result object.
    var $_size; # Number of entries in result set.

    function cursor_sql ()
    {
        $this->cursor ('sql');
        $this->_db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
    }

    # Set database connection for all cursor_sql instances.
    # (Static function.)
    static function set_db (&$db)
    {
        if ($GLOBALS['__CURSOR_SQL_INSTANCE'])
            die_traced ('Connection already set.');
        $GLOBALS['__CURSOR_SQL_INSTANCE'] =& $db;
    }

    function set_preset_values ($pre)
    {
        return $this->_pre = $pre;
    }

    # Perform query and read the first row
    function _query ($where = '', $order = '')
    {
        $db =& $this->_db;
        $def =& $db->def;
        $table = $this->_source;

        $this->_size = 0;
        $this->_get_next_id = 0;
        if (!$res = $db->select ('*', $table, sql_append_string ($where, sql_selection_assignments ($this->_pre), " AND "), $order))
            return;
        $this->_size = $res->num_rows ();

        if ($is_list = $def->is_list ($table)) {
            $pri = $def->primary ($table);
            $res = $db->select ('*', $table, (strpos ($where, "$pri=") === 0 ?
                                              $where : sql_append_string ($where,
                                                                          sql_selection_assignments (array_merge ($this->_pre,
                                                                                                                   array ($def->id_prev ($table) => 0))),
                                                                                                     " AND ")));
            $row = $res->get ();
            $this->_get_next_id = $row[$pri];
        }

        $this->_res = $res;
        return true;
    }

    function _get ()
    {
        $db =& $this->_db;
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
            $this->_get_next_id = $row[$def->id_next ($table)];

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

        $db =& $this->_db;
        $primary = $db->def->primary ($this->_source);
        $db->update ($this->_source, sql_assignment ($field, $value), sql_assignment ($primary, $this->_key));
    }

    function create ($values = 0)
    {
        return $this->_db->append_new ($this->_source, $values, $this->_pre);
    }

    function delete ()
    {
        $db =& $this->_db;
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
    function __sleep ()
    {
        $elements = cursor::__sleep ();
        $elements[] = '_size';
        return $elements;
    }

    function __wakeup ()
    {
        $this->_db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
    }
}

?>

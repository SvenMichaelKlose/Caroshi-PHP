<?php
# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once 'cursor/cursor.class.php';

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
    function set_db (&$db)
    {
        if ($GLOBALS['__CURSOR_SQL_INSTANCE'])
            die ('cursor_sql::set_db(): Connection already set.');
        $GLOBALS['__CURSOR_SQL_INSTANCE'] =& $db;
    }

    # Perform query and read the first row
    function _query ($whereclause = '', $order = '')
    {
        $db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
        $def =& $db->def;
        $v =& $this;

        $table = $v->_source;
        $pri = $def->primary ($table);
        $size =& $this->_size;
        $size = 0;

        # Start with query that fetches the whole table.
        $q = "SELECT * FROM " . $db->table_prefix ($table);

        if ($whereclause)
            $q .= " WHERE $whereclause";

        # If we have a list limit query to record without reference to previous
        # one.
        if ($is_list = $def->is_list ($table)) {
            $w = ' ' . $def->prev_of ($table) . '=0';
            $qp = ($whereclause ? ' AND ' : ' WHERE ') . $w;

            $res =& $db->query ($q . $qp);
            $size = $res->num_rows ();
            if ($size < 1) {
	        # Couldn't fetch list start, try without next record.
                $res =& $db->query ($q);
                $size = $res->num_rows ();
                if ($size < 1)
                    return false;
	    }

            # Remember reference to first record.
            $row =& $res->get ();
            $v->_get_next_id = $row[$pri];
        } else {
            $res =& $db->query ("$q $order");
            $size = $res->num_rows ();
            if ($size < 1)
                return false;
        }

        $v->_res =& $res;
        return true;
    }

    function &_get ()
    {
        $db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
        $def =& $db->def;
        unset ($this->_current);

        # Current view's source of information is specified by a table name.
        $table = $this->_source;
        $pri = $def->primary ($table);

        # If record is stored in a list, do a new query using the reference in
        # the last record stored in $v->_get_next_id.
        $is_list = $def->is_list ($table);
        if ($is_list) {
            if (!isset ($this->_get_next_id))
	        return false;

            $wtable = $db->table_prefix ($table);
            $nid = $this->_get_next_id;
            $q = "SELECT * FROM $wtable WHERE $pri='$nid'";
            $v->_res =& $db->query ($q);
            if ($v->_res->num_rows () < 1)
                return false;
        }

        # Fetch next record.
        $row =& $this->_res->get ();
        if ($e = $db->error ())
            $this->panic ($e);

        # Remember reference to next record.
        if ($is_list)
            $this->_get_next_id = $row[$def->next_of ($table)];

        # Store record key.
        if ($pri)
            $this->_key = $row[$pri];

        return $row;
    }

    # Update a row's field.
    function set ($value)
    {
        $db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
        $def =& $db->def;
        $source = $this->_source;
        $key = addslashes ($this->_key);
        $field = $this->_field;
        if (!$field)
            die ('cursor_sql::set(): No field to set.');

        $types = $def->types ($source);
        if (!isset ($types[$field]))
	    die ("cursor_sql::set(): No field '$field' in table '$source'<br>");
        $db->update ($source, $field . '="' . $value . '"',
                     $def->primary ($source) . '="' . $key . '"');
    }

    function create ($pre = 0, $parent = 0)
    {
        $db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
        $key = isset ($this->_key) ? addslashes ($this->_key) : 0;

        return $db->append_new ($this->_source, $key, $pre);
    }

    function delete ()
    {
        $db =& $GLOBALS['__CURSOR_SQL_INSTANCE'];
        $source = $this->_source;
        if (!$source)
            die ('cursor_sql::delete(): No source.');
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

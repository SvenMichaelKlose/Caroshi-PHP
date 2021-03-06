<?php
# Copyright (c) 2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once PATH_TO_CAROSHI . '/cursor/cursor.class.php';

$__CURSOR_DBCONF_INSTANCE = 0;

/**
 * Cursor for dbconf object content.
 *
 * @access public
 * @package Cursor interfaces
 */
class cursor_dbconf extends cursor {
    var $conf;	# Reference to dbconf object.

    function cursor_dbconf (&$dbconf)
    {
        global $__CURSOR_DBCONF_INSTANCE;
        if (!$__CURSOR_DBCONF_INSTANCE)
            die_traced ('Use set_dbconf() before.');
        $this->cursor ('dbconf');
        $this->conf =& $__CURSOR_DBCONF_INSTANCE;
    }

    function set_dbconf (&$dbconf)
    {
        if ($GLOBALS['__CURSOR_DBCONF_INSTANCE'])
            die_traced ('Connection already set.');
        $GLOBALS['__CURSOR_DBCONF_INSTANCE'] =& $dbconf;
    }

    function &_query ($prefix)
    {
        global $config_table;

        return $this->_res = $this->conf->db->select ('*', $config_table, "name LIKE '$prefix%'", 'ORDER BY descr ASC');
    }

    function &_get ()
    {
        $row = $this->_res && $this->_res->get ();

        # Set record key.
        $this->_key = $row['name'];

        $this->_current =& $row;
        return $row;
    }

    function set ($value)
    {
        $source = $this->_source;
        $key = addslashes ($this->_key);
        $field = $this->_field;
        if (!$field)
            die_traced ('No field to set.');

        if (!isset ($this->conf))
            $this->conf =& $GLOBALS['__CURSOR_DBCONF_INSTANCE'];

        switch ($field) {
	    case 'is_file':
	        $is_file = $value;
	        $value = $this->conf->get ($key);
	        break;

	    case 'data':
	        $is_file = $this->conf->is_file ($key);
	        break;

	    default:
	        return;
        }

        $this->conf->set ($key, stripslashes ($value), $is_file);
    }
}
?>

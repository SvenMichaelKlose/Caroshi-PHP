<?php
  # $Id: dbconf.class.php,v 1.4 2002/05/31 19:21:53 sven Exp $
  #
  # Form interface to dbconf objects.
  #
  # Copyright (c) 2001 dev/consulting GmbH
  #                    Sven Michael Klose <sven@devcon.net>
  #
  # This library is free software; you can redistribute it and/or
  # modify it under the terms of the GNU Lesser General Public
  # License as published by the Free Software Foundation; either
  # version 2.1 of the License, or (at your option) any later version.
  #
  # This library is distributed in the hope that it will be useful,
  # but WITHOUT ANY WARRANTY; without even the implied warranty of
  # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
  # Lesser General Public License for more details.
  #
  # You should have received a copy of the GNU Lesser General Public
  # License along with this library; if not, write to the Free Software
  # Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

  require_once 'cursor/cursor.class.php';

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
        die ('cursor_dbconf::cursor_dbconf(): Use set_dbconf() before.');
      $this->cursor ('dbconf');
      $this->conf =& $__CURSOR_DBCONF_INSTANCE;
    }

    function set_dbconf (&$dbconf)
    {
      if ($GLOBALS['__CURSOR_DBCONF_INSTANCE'])
        die ('cursor_sql::set_db(): Connection already set.');
      $GLOBALS['__CURSOR_DBCONF_INSTANCE'] =& $dbconf;
    }

    function &_query ($prefix)
    {
      global $config_table;

      $this->_res =& $this->conf->db->select (
        '*', $config_table, "name LIKE '$prefix%'", 'ORDER BY descr ASC'
      );
      return ($this->_res->num_rows () < 1) ? false : true;
    }

    function &_get ()
    {
      $row =& $this->_res->get ();

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
        die ('cursor_dbconf::set(): No field to set.');

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

<?php
  # *** DO NOT USE THIS FILE! ***

  # $Id: php_array.class.php,v 1.5 2002/05/31 19:21:53 sven Exp $
  #
  # Cursor over php array.
  #
  # Copyright (c) 2000-2001 dev/consulting GmbH
  #                         Sven Michael Klose <sven@devcon.net>
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

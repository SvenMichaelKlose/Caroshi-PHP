<?php
  # $Id: iterator_array.class.php,v 1.2 2002/06/23 14:51:00 sven Exp $
  #
  # Copyright (c) 2002 Sven Michael Klose (sven@devcon.net)
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

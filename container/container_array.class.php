<?php
  # $Id: container_array.class.php,v 1.2 2002/06/23 14:51:00 sven Exp $
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

  require_once "container/container.class.php";
  require_once "container/iterator_array.class.php";

  /**
   * Array container
   *
   * For method details please see the container superclass.
   *
   * @access public
   * @package Containers
   */
  class container_array extends container {

     /**
      * Construct container.
      *
      * @access public
      * @param array $array Array to assign.
      */
    function container_array (&$array)
    {
      $this->container ('container_array');
      $this->assign ($array);
    }

     /**
      * Assign new array to container.
      *
      * @access public
      * @param array $array Array to assign.
      */
    function assign (&$array)
    {
      $this->_array =& $array;
      $this->_update ();
    }

    function _update ()
    {
      $this->_keys =& array_keys ($this->_array);
    }

    function begin ()
    {
      return new iterator_array ($this, 0);
    }

    function end ()
    {
      return new iterator_array ($this, sizeof ($this->_keys));
    }

    function size ()
    {
      return sizeof ($this->_keys);
    }

    function insert ($iterator, $key, $element)
    {
      $size = sizeof ($this->_keys);
      $pos = $iterator->_pos;
      $old =& $this->_array;
      unset ($old[$key]);
      $new = array ();

      # Copy all elements to a new array including new element.
      for ($i = 0; $i < $pos; $i++) {
        $k = $this->_keys[$i];
        $new[$k] =& $old[$k];
      }
      $new[] = $element;
      for ($i = $pos; $i < $size; $i++) {
        $k = $this->_keys[$i];
        $new[$k] =& $old[$k];
      }

      $this->_array =& $new;
      $this->_update ();
    }

    var $_array;
    var $_keys;  # Array of keys of the array.
  }
?>

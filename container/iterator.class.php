<?php
  # $Id: iterator.class.php,v 1.2 2002/06/23 14:51:00 sven Exp $
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

  require_once "object/abstract.class.php";

  /**
   * Iterator superclass.
   *
   * @access public
   * @package Containers
   */
  class iterator extends abstract {

    /**
     * Construct iterator.
     *
     * @access private
     * @param object container $ct Reference of container the iterator
     *                             belongs to.
     */
    function iterator (&$ct)
    {
      $this->abstract ('iterator');
      $this->_ct =& $ct;
    }

    /**
     * Return element the iterator points to.
     *
     * @access public
     * @returns mixed Data type depends on the container type.
     */
    function &current ()
    {
      abstract::call_pure_virtual ('current');
    }

    /**
     * Advance iterator.
     *
     * @access public
     * @param integer $distance Number of elements to advance. The default is
     *                          to step to the next element. Negative values
     *                          will move the iterator backwards.
     */
    function advance ($distance = 1)
    {
      abstract::call_pure_virtual ('advance');
    }

    /**
     * Check if iterator is usable.
     *
     * @access public
     * @returns boolean
     */
    function &good ()
    {
      abstract::call_pure_virtual ('good');
    }

    /**
     * Return element the iterator points to and advance it.
     *
     * @access public
     * @param integer $distance Number of elements to advance.
     * @returns mixed Data type depends on the container type.
     */
    function &get ($distance = 1)
    {
      $rec =& $this->get ();
      $this->advance ($distance);
      return $rec;
    }

    var $_ct;     # Reference to container.
    var $_is_end; # True if iterator points to the end of a list.
  }
?>

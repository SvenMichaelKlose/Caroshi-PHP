<?php
  # Copyright (c) 2002 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


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

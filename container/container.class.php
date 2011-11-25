<?php
  # Copyright (c) 2002 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


  require_once "object/abstract.class.php";

  /**
   * Container superclass
   *
   * @access public
   * @package Containers
   */
  class container extends abstract {

    /**
     * Initialise container.
     *
     * @access private
     * @param string container $derived_class Name of derived class.
     */
    function container ($derived_class)
    {
      $this->abstract ('container', $derived_class);
    }

    /**
     * Assign new data to container.
     *
     * @access public
     * @param mixed $data
     */
    function assign ($data)
    {
      abstract::call_pure_virtual ('erase');
    }

    /**
     * Remove element.
     *
     * @access public
     * @param object iterator $iterator
     */
    function erase ($iterator)
    {
      abstract::call_pure_virtual ('erase');
    }

    /**
     * Insert new element.
     *
     * The element is inserted _before_ the element the iterator references.
     *
     * @access public
     * @param object iterator $iterator
     * @param mixed $record
     */
    function insert ($iterator, $record)
    {
      abstract::call_pure_virtual ('insert');
    }

    /**
     * Return iterator for the first element.
     *
     * @access public
     * @returns object iterator
     */
    function &begin ()
    {
      abstract::call_pure_virtual ('begin');
    }

    /**
     * Return iterator for the end of the element list.
     *
     * The iterator points _after_ the last element in the list.
     *
     * @access public
     * @returns object iterator
     */
    function &end ()
    {
      abstract::call_pure_virtual ('end');
    }

    /**
     * Add element to front of list.
     *
     * @access public
     * @param mixed $record
     */
    function push_front ($record)
    {
      $it =& $this->begin ();
      $this->insert ($it, $record);
    }

    /**
     * Add element to end of list.
     *
     * @access public
     * @param mixed $record
     */
    function push_back ($record)
    {
      $it =& $this->end ();
      $this->insert ($it, $record);
    }

    /**
     * Return first element from list.
     *
     * The returned element is removed from the container.
     *
     * @access public
     * @param mixed $record
     */
    function pop_front ()
    {
      $it =& $this->begin ();
      $this->erase ($it);
    }

    /**
     * Return last element from list.
     *
     * The returned element is removed from the container.
     *
     * @access public
     * @param mixed $record
     */
    function pop_back ()
    {
      $it =& $this->end ();
      $it->advance (-1);
      $this->erase ($it);
    }

  }
?>

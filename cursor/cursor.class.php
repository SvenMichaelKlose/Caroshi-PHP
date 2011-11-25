<?php
  # Copyright (c) 2001-2002 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


  require_once 'object/is_a.php';

  /**
   * Cursor base class
   *
   * @access public
   * @package Cursor interfaces
   */
  class cursor {

    /**
     * Current view's source name.
     * @access private
     * @var string
     */
    var $_source = '';

    /**
     * Current record key's value.
     * @access private
     * @var string
     */
    var $_key = 0;

    /**
     * Currently opened field.
     * @access private
     * @var string
     */
    var $_field = '';

    /**
     * # Name of SSI.
     * @access private
     * @var string
     */
    var $_type;

    /**
     * Lower cursor.
     * @access private
     * @var string
     */
    var $_lower;

    /**
     * Do we have a record set?
     * @access private
     * @var string
     */
    var $_is_good = false;

    /**
     * Last read record.
     * @access private
     * @var string
     */
    var $_current;

    /**
     * Last record was first but read with current().
     * @access private
     * @var string
     */
    var $_is_first = false;

    /**
     * query() was called.
     * @access private
     * @var string
     */
    var $_is_queried = false;

    /**
     * Last query selection.
     * @access private
     * @var mixed
     * @see query,get(),__wakeup()
     */
    var $_selection = false;

    /**
     * Last query order.
     * @access private
     * @var mixed
     * @see query(),get(),__wakeup()
     */
    var $_order = false;

    /**
     * Number of gets that need to be done after wakeup.
     * @access private
     * @var integer
     * @see query(),get(),__wakeup()
     */
    var $_num_gets = 0;

    /**
     * Set, if cursor needs to restore the result set.
     * @access private
     * @var boolean
     * @see query(),get(),__wakeup(),_restore_result()
     */
    var $_waked_up = false;

    /**
     * Create cursor and set its type.
     *
     * @access private
     * @param string $type Type name.
     * @see type()
     */
    function cursor ($type)
    {
      $this->_type = $type;
    }

    /**
     * Query a result set.
     *
     * The argument syntax depends on the derived class.
     *
     * @access public
     * @param string $selection Selection of records.
     * @param string $order Order of results.
     * @returns object error Error object or 0.
     * @see current(),get()
     */
    function query ($selection = '', $order = '')
    {
      $this->_is_first = false;
      $this->_waked_up = false;
      $ret = $this->_query ($selection, $order);
      $this->_is_queried = $this->_is_good = $ret;
      if ($ret) {
        $this->_selection = $selection;
        $this->_order = $order;
      } else {
        $this->_selection = '';
        $this->_order = '';
      }
      $this->_num_gets = 0;
      return $ret;
    }

    /**
     * Return first or last get'ed() result in queried set.
     *
     * @access public
     * @returns mixed Record.
     * @see key(), get()
     */
    function &current ()
    {
      if (!$this->_is_good)
        return;

      if ($this->_is_queried)
        return $this->_current;

      # Get first record.
      if (!$this->get ())
        return;
      $this->_is_first = true;

      return $this->_current;
    }

    /**
     * Return first or next record.
     *
     * The argument syntax depends on the derived class.
     *
     * @access public
     * @param string $selection Selection of records.
     * @param string $order Order of results.
     * @returns mixed Record.
     * @see key(), current()
     */
    function &get ($selection = '', $order = '')
    {
      if ($this->_waked_up)
        $this->_restore_result ();

      $this->_num_gets++;

      # Query if selection is defined.
      if ($selection)
        $this->query ($selection, $order);
      else if ($this->_is_first) {
        # Return first record get'ed by current().
        $this->_is_first = false;
        return $this->_current;
      }

      if (!$this->_is_good)
        return; # Nothing queried.

      $ret =& $this->_get ();
      $this->_current = $ret;
      $this->_is_good = $ret ? true : false;
      return $ret;
    }

    /**
     * Number of records in result set.
     *
     * This function is overwritten by derived classes.
     *
     * @access public
     * @returns int Number of records.
     */
    function size ()
    {
      die ('cursor::size(): Function not implemented by derived class.');
    }

    /**
     * Create an unique id for the current record.
     *
     * @access public
     * @returns string The length is undefined.
     */
    function id ()
    {
      $id = '';
      foreach (array ('_type', '_source', '_key', '_field') as $v) {
        if (isset ($this->$v))
          $id .= $this->$v;
        $id .= "'";
      }
      if (isset ($this->_lower))
        $id .= $this->_lower->id ();
      return $id;
    }

    /**
     * Set source name.
     *
     * @access public
     * @returns string Source name.
     * @see source()
     */
    function set_source ($source)
    {
      $this->_source = $source;
    }

    /**
     * Get source name.
     *
     * @access public
     * @returns string The length is undefined.
     * @see set_source()
     */
    function source ()
    {
      return $this->_source;
    }

    /**
     * Set record key.
     *
     * Setting the key has no effect on the referenced record.
     * This only affects method key(),
     *
     * @access public
     * @returns string Source name.
     * @see key()
     */
    function set_key ($key)
    {
      $this->_key = $key;
    }

    /**
     * Get current record's key.
     *
     * @access public
     * @returns mixed The length is undefined.
     * @see set_key()
     */
    function key ()
    {
      return $this->_key;
    }

    /**
     * Select a record field.
     *
     * This only affects methods id() and field()
     *
     * @access public
     * @returns string Field name.
     * @see id(), field()
     */
    function set_field ($field)
    {
      $this->_field = $field;
    }

    /**
     * Get currently selected field.
     *
     * @access public
     * @returns mixed The length is undefined.
     * @see set_field()
     */
    function field ()
    {
      return $this->_field;
    }

    /**
     * Get currently selected field.
     *
     * @access public
     * @returns string The length is undefined.
     * @see cursor()
     */
    function type ()
    {
      return $this->_type;
    }

    /**
     * Serialise no more than the reference info.
     *
     * Derived classes must add their variables in their own __sleep()
     * method.
     *
     * @access private
     * @returns array List of variable name to serialize.
     */
    function __sleep ()
    {
      return array ('_source', '_key', '_field', '_type', '_lower',
                    '_selection', '_order', '_num_gets');
    }

    /**
     * Restore result set.
     *
     * @access private
     * @returns array List of variable name to serialize.
     */
    function _restore_result ()
    {
      $this->_waked_up = false;
      $num = $this->_num_gets;
      $this->query ($this->_selection, $this->_order);
      while ($num--)
        $this->get ();
    }

    /**
     * Mark cursor as waked-up.
     *
     * This will force get() to restore the result set.
     *
     * @access private
     * @returns array List of variable name to serialize.
     */
    function __wakeup ()
    {
      $this->_waked_up = true;
    }
  }
?>

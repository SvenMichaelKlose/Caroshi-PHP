<?php

# Copyright (c) 2001-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once PATH_TO_CAROSHI . '/object/is_a.php';


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
     * Last read record.
     * @access private
     * @var string
     */
    var $_current;

    /**
     * Last query selection.
     * @access private
     * @var mixed
     * @see query,get()
     */
    var $_selection = false;

    /**
     * Last query's order.
     * @access private
     * @var mixed
     * @see query(),get()
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
     * Tells if a query was performed.
     * @access private
     * @var integer
     * @see query(),get(),__wakeup()
     */
    var $_did_query = false;

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
        $this->_did_query = true;
        $this->_selection = $selection;
        $this->_order = $order;
        $this->_num_gets = 0;
        return $this->_query ($selection, $order);
    }

    /**
     * Return first or last get'ed() result in queried set.
     *
     * @access public
     * @returns mixed Record.
     * @see key(), get()
     */
    function current ()
    {
        return $this->_current;
    }

    /**
     * Return field of last fetched record.
     *
     * @access public
     * @returns mixed Record.
     * @see key(), get()
     */
    function value ($name)
    {
        return $this->_current[$name];
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
    function get ($selection = '', $order = '')
    {
        $ret = $this->_get ();
        $this->_current = $ret;
        if ($ret)
            $this->_num_gets++;
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
        die_traced ('Function not implemented by derived class.');
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
     * @returns mixed
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
     * @returns mixed
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
     * This only affects methods key() and id(),
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
     * Get type name.
     *
     * @access public
     * @returns string The cursor's type name.
     * @see cursor()
     */
    function type ()
    {
        return $this->_type;
    }

    /**
     * Magic function to tell PHP which properties to serialize,
     *
     * Derived classes must add their variables in their own __sleep()
     * method.
     *
     * @access private
     * @returns array List of variable name to serialize.
     */
    function __sleep ()
    {
        return array ('_source', '_key', '_field', '_type', '_selection', '_order', '_did_query', '_num_gets');
    }

    /**
     * Magic function called by PHP after unserialization.
     *
     * Repeats query() and number of calls to get().
     *
     * @access private
     * @returns array List of variable name to serialize.
     */
    function __wakeup ()
    {
        if (!$this->_did_query)
            return;
        $num = $this->_num_gets;
        $this->query ($this->_selection, $this->_order);
        while ($num--)
            $this->get ();
    }
}

?>

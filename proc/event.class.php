<?php
# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


/**
 * Event object.
 *
 * @access public
 * @package Application server
 */
class event {
    /**
     * Create event object.
     *
     * @access public
     * @param string $name Function or method name.
     * @param array $arg Array of arguments.
     */
    function event ($name, $args = 0)
    {
        type_string ($name);
        if (!$args)
            $args = array ();
        type_array ($args);

        $this->name = $name;
        $this->args = $args;
    }

    /**
     * Copy event object.
     *
     * @access public
     * @returns object event
     */
    function copy ()
    {
        return new event ($this->name, $this->args);
    }

    /**
     * Get an argument.
     *
     * @access public
     * @param string $name Argument name.
     * @returns mixed
     */
    function arg ($name)
    {
        type_string ($name);

        if (isset ($this->args[$name]))
            return $this->args[$name];
    }

    /**
     * Set next event that must be called after this one.
     *
     * @access public
     * @param object event $e
     * @returns object event Itself.
     */
    function set_next ($e)
    {
        if (is_string ($e))
            $e = new event ($e);
        if (!is_a ($e, 'event'))
            die_traced ('event::set_next(): Argument is not an event object.');
        $this->next = $e;
        return $e;
    }

    /* Set return function for call to subsession.
     *
     * If the caller isn't set, no new subsession is opened.
     *
     * @access public
     * @param object event $e Function to return to.
     * @returns object event Itself.
     */
    function set_caller ($e)
    {
        type ($e, 'event');

        # Call subsession function.
        $c = new event ('__call_sub', array ('caller' => $e));
        $t = $this;
        $c->set_next ($t);
        return $e;
    }

    /**
     * Set an argument.
     *
     * @access public
     * @param string $name Argument name.
     * @param mixed $data New argument data.
     * @returns mixed New rgument data.
     */
    function set_arg ($name, $data)
    { 
        type_string ($name);

        $this->args[$name] = $data;
    }

    /**
     * Remove an argument.
     *
     * @access public
     * @param string $name Argument name.
     */
    function remove_arg ($name)
    { 
        unset ($this->args[$name]);
    }

    var $name;
    var $args;
    var $next = 0;
    var $subsession;
}
?>

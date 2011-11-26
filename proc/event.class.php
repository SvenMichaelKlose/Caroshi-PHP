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
        if (!is_string ($name)) {
            debug_dump ($name);
            die ('event::event(): Handler name is not a string.');
        }
        if (!$args)
            $args = array ();
        else if (!is_array ($args))
            die ("event::event(): args is not an array for handler '$name'.");

        $this->name = $name;
        $this->args = $args;
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
        if (!is_string ($name))
            die ('event::arg(): Argument name is not a string.');

        $a =& $this->args[$name];
        if (isset ($a))
            return $a;
    }

    /**
     * Set next event that must be called after this one.
     *
     * @access public
     * @param object event $e
     */
    function set_next (&$e)
    {
        if (is_string ($e))
            $e = new event ($e);
        if (!is_a ($e, 'event'))
            die ('event::set_next(): Argument is not an event object.');
        $this->next =& $e;
    }

    /* Set return function for call to subsession.
     *
     * If the caller isn't set, no new subsession is opened.
     *
     * @access public
     * @param object event $e Function to return to.
     */
    function set_caller ($e)
    {
        if (!is_a ($e, 'event'))
            die ('event::set_caller(): Argument is not an event object.');

        # Call subsession function.
        $c = new event ('__call_sub', array ('caller' => $e));
        $t = $this;
        $c->set_next ($t);
    }

    /**
     * Set an argument.
     *
     * @access public
     * @param string $name Argument name.
     * @param mixed $data New argument data.
     */
    function set_arg ($name, $data)
    { 
        if (!is_string ($name))
            die ('event::set_arg(): Argument name is not a string.');

        $this->args[$name] = $data;
    }

    var $name;
    var $args;
    var $next = 0;
    var $subsession;
}
?>

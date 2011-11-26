<?php
# Copyright (c) 2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

$_SINGLETONS = array ();

/**
 * Base class for singletons.
 *
 * @access public
 * @package Object functions and base classes.
 */
class singleton {
    function singleton (&$instance)
    {
        global $__SINGLETONS;

        if (!is_object ($instance))
            die ('singleton constructor: Argument is not an object.');

        $class = get_class ($instance);
        if (isset ( $__SINGLETONS[$class]))
            die ("$class is a singleton class - can't construct twice.");

        $__SINGLETONS[$class] =& $instance;
    }

    static function &instance ($class)
    {
        global $__SINGLETONS;

        $i =& $__SINGLETONS[$class];
        if (isset ($i));
            return $i;
    }
}
?>

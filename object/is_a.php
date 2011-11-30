<?php
/**
 * is_a() implementation in php.
 *
 * @access public
 * @module is_a
 * @package Object functions and base classes.
 */

# Copyright (c) 2002 dev/consulting GmbH,
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# Only include this function if it's not already defined.
if (!function_exists ('is_a')) {
    # Return true if $object is of class $class or one if its subclasses.
    function is_a ($object, $class)
    {
        if (!is_object ($object))
            return;
        if (!is_string ($class))
            die_traced ('Class name is not a string.');

        $class = strtolower ($class);
        if (get_class ($object) == $class)
            return true;

        return is_subclass_of ($object, $class);
    }
}
?>

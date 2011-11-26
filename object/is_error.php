<?php
/**
 * is_error()
 *
 * Check if variable contains an error object.
 *
 * @access public
 * @module is_error
 * @package Object functions and base classes.
 */

# Copyright (c) 2002 dev/consulting GmbH,
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once 'object/error.class.php';

/**
 * Check if variable contains an error object.
 *
 * @access public
 * @param mixed $var Variable to check,
 * returns bool
 */
function is_error (&$var)
{
    return is_a ($var, 'error');
}
?>

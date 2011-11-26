<?php
/**
 * Check if $haystack starts with $needle. Return true if it does.
 *
 * @access public
 * @module strhead
 * @package String functions
 */

# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


/**
 * Check if $haystack starts with $needle. Return true if it does.
 *
 * @access public
 * @param string $haystack
 * @param string $needle
 * @returns bool
 */
function strhead ($haystack, $needle)
{
    if (substr ($haystack, 0, strlen ($needle)) == $needle)
        return true;
    return false;       
}
?>

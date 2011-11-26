<?php
/**
 * Profiling functions
 *
 * @access public
 * @module profile
 * @package Application server
 */

# Copyright (c) 2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


/**
 * Start timer.
 *
 * @access public
 * @param string $name Name of timer.
 */
function profile_start ($name)
{
    global $_prof, $_prof_times;

    if (!isset ($_prof[$name])) {
        $_prof[$name] = array ();
        $_prof_times[$name] = 0;
    }
    array_push ($_prof[$name], gettimeofday ());
}
 
/**
 * Get time from timer.
 *
 * @access public
 * @param string $name Name of timer.
 * @returns float Time passed since timer start in seconds.
 */
function profile_end ($name)
{
    global $_prof, $_prof_times;

    $t = gettimeofday ();
    $t_start = array_pop ($_prof[$name]);
    $_prof_times[$name] +=
        (($t['usec'] + $t['sec'] * 1000000) -
        ($t_start['usec'] + $t_start['sec'] * 1000000)) / 1000000;
}
?>

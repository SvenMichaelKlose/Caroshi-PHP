<?php
/**
 * Debug dump function
 *
 * @access public
 * @module debug_dump
 * @package Application server
 */

# Copyright (C) 2001-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# Debug levels.
define ('DEBUG_DB', 1);
define ('DEBUG_VIEWS', 2);

/**
 * Dump a variable to HTML.
 *
 * @access public
 */
function debug_dump ($a)
{
    if (is_array ($a)) {
        ksort ($a);
        echo '<table border="1" cellpadding="0" cellspacing="0">';
        foreach ($a as $k => $v) {
            echo "<tr><td bgcolor=\"white\">$k&nbsp;</td><td bgcolor=\"silver\">";
            if (is_array ($v))
                debug_dump ($v);
            else {
	        if (is_bool ($v))
	            echo '<i>' . ($v ? 'true' : 'false') . '</i>';
                else if (is_string ($v))
	            echo '&quot;' . htmlentities (addslashes ($v)) . '&quot;';
                else if (is_object ($v)) {
                    echo "<pre>";
                    print_r ($v);
                    echo "<pre>";
                } else
                    echo "$v&nbsp;";
            }
            echo '</td></tr>';
        }
        echo '</table>';
    } else if (is_null ($a)) {
        echo '<table border="1" cellpadding="0" cellspacing="0"><tr><td bgcolor="silver"><i>NULL</i></td></tr></table>';
    } else {
        echo '<table border="1" cellpadding="0" cellspacing="0"><tr><td bgcolor="silver">';
        $tmp = array ($a);
        debug_dump ($tmp);
        echo '&nbsp;</td></tr></table>';
    }

    flush ();
}

/**
 * Dump environment variables.
 *
 * Only variables HTTP_SERVER_VARS, HTTP_ENV_VARS and HTTP_POST_VARS are
 * dumped.
 *
 * @access public
 */
function debug_env_dump ()
{
    $tmp = array ('HTTP_SERVER_VARS' => $GLOBALS['HTTP_SERVER_VARS'],
                  'HTTP_ENV_VARS' => $GLOBALS['HTTP_ENV_VARS'],
                  'HTTP_POST_VARS' => $GLOBALS['HTTP_POST_VARS']);
    debug_dump ($tmp);
}
?>

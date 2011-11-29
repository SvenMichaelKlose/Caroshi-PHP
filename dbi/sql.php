<?php

/**
 * SQL utiliy functions.
 *
 * @access public
 * @module dbsort
 * @package Database interfaces
 * @author Sven Michael Klose
 */

# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function sql_value ($v)
{
    type_string ($v);
    return mysql_real_escape_string ($v);
}

function sql_quote ($v)
{
    $v && type_string ($v);
    return '"' . mysql_real_escape_string ($v) . '"';
}

function sql_assignment ($k, $v)
{
    return sql_value ($k) . '=' . sql_quote ($v);
}

function sql_append_assignment ($x, $k, $v, $padding = ', ')
{
    return $x . ($x ? $padding : '') . sql_assignment ($k, $v);
}

/**
 * Convert array to SQL assignment list.
 *
 * @access public
 * @param array $values Field values keyed by their names.
 * @returns string
 * @package Database interfaces
 */
function sql_assignments ($values, $padding = ', ')
{
    type_array ($values);

    $x = '';
    foreach ($values as $k => $v)
        if (!is_numeric ($k))
            $x = sql_append_assignment ($x, $k, $v, $padding);
    return $x;
}

?>

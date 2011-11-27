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
    if (!is_string ($v))
        die ("sql_value: $v is not a string.");
    return mysql_real_escape_string ($v);
}

function sql_quote ($v)
{
    if ($v && !is_string ($v))
        die ("sql_quote: $v is not a string.");
    return '"' . mysql_real_escape_string ($v) . '"';
}

function sql_assignment ($k, $v)
{
    return sql_value ($k) . '=' . sql_quote ($v);
}

function sql_append_assignment ($x, $k, $v)
{
    return $x . ($x ? ',' : '') . sql_assignment ($k, $v);
}

/**
 * Convert array to SQL assignment list.
 *
 * @access public
 * @param array $values Field values keyed by their names.
 * @returns string
 * @package Database interfaces
 */
function sql_array_assignments ($values)
{
    if (!is_array ($values))
        die ('sql_array_assignments: Argument is not an array.');

    $x = '';
    foreach ($values as $k => $v)
        sql_append_assignment ($x, $k, $v);
    return $x;
}

?>

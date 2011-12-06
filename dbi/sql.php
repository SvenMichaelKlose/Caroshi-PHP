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

function sql_append_string ($head, $tail, $padding = ', ')
{
    return $head . (($head && $tail) ? $padding : '') . $tail;
}

function sql_append_assignment ($head, $k, $v, $padding = ', ')
{
    $tail = sql_assignment ($k, $v);
    return sql_append_string ($head, $tail, $padding);
}

/**
 * Convert array to comma-separated SQL assignment list.
 *
 * @access public
 * @param array $values Field values keyed by their names.
 * @returns string
 * @package Database interfaces
 */
function sql_assignments ($values, $padding = ', ')
{
    $values && type_array ($values);

    $x = '';
    if ($values)
        foreach ($values as $k => $v)
            if (!is_numeric ($k))
                $x = sql_append_assignment ($x, $k, $v, $padding);
    return $x;
}

/**
 * Convert array to ANDed SQL assignment list.
 *
 * @access public
 * @param array $values Field values keyed by their names.
 * @returns string
 * @package Database interfaces
 */
function sql_selection_assignments ($values)
{
    return sql_assignments ($values, ' AND ');
}

function selection_has_primary ($def, $table, $selection)
{
    return strpos ($selection, $def->primary ($table)) === 0;
}

?>

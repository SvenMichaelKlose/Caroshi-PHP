<?php
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

function type_0 ($x, $type)
{
    switch ($type) {
        case 'bool':
            return is_bool ($x);
        case 'array':
            return is_array ($x);
        case 'float':
            return is_float ($x);
        case 'int':
            return is_int ($x);
        case 'null':
            return is_null ($x);
        case 'numeric':
            return is_numeric ($x);
        case 'object':
            return is_object ($x);
        case 'string':
            return is_string ($x);
        default:
            return is_a ($x, $type);
    }
}

function type ($x, $typelist)
{
    $args = func_get_args ();
    array_shift ($args);
    foreach ($args as $type)
        if (type_0 ($x, $type))
            return;

    $trace = debug_backtrace ();
    foreach ($args as $type)
        $req .= ($req ? ', ' : '') . $type;
    die ("In file <b>'" . $trace[1][file] . "'</b> on line <b>" . $trace[1][line] . "</b>: " .
         "Type assertion found <b><i>'" . gettype ($x) . "'</i></b> found but need one of these: <b><i>$req</i></b>.");
}

function type_bool ($x) { return type ($x, 'bool'); }
function type_array ($x) { return type ($x, 'array'); }
function type_float ($x) { return type ($x, 'float'); }
function type_int ($x) { return type ($x, 'int'); }
function type_null ($x) { return type ($x, 'null'); }
function type_numeric ($x) { return type ($x, 'numeric'); }
function type_object ($x) { return type ($x, 'object'); }
function type_string ($x) { return type ($x, 'string'); }

?>

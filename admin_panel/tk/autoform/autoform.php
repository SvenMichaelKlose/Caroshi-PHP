<?php

/**
 * Generic editor for records in a single table.
 *
 * Needs $this->db->dep; (dbi/autoform.class instance)
 *
 * @access public
 * @module tk_autoform
 * @package User interface toolkits
 */


# Copyright (c) 2001-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# Widget generation flags.
define ('TK_AUTOFORM_LABELS',   1); # Powers of 2.
define ('TK_AUTOFORM_NO_INPUT', 2);

/**
 * Initialise toolkit. Call this in your init() function.
 *
 * @access public
 * @param object application $app
 */
function tk_autoform_init (&$app)
{
}

/**
 * Create a widget from dbdepend description.
 *
 * @access public
 * @param object application $app
 * @param string $source Source name.
 * @param string $field Field name.
 * @param string $flags TK_AUTOFORM_LABELS or TK_AUTOFORM_NO_INPUT.
 */
function tk_autoform_create_widget (&$app, $cursor, $type, $field, $flags = 0)
{
    $p =& $app->ui;
    $def =& $app->db->def;

    # Don't print hidden fields.
    if (isset ($type['tk_autoform']['hide']))
        return;

    $p->open_row ();
    if ($flags & TK_AUTOFORM_LABELS)
        $p->label (isset ($type['d']) ? $type['d'] : $field);
    _tk_autoform_create_widget ($app, $cursor, $type, $field, $flags);
    $p->close_row ();
}

function _tk_autoform_create_widget (&$app, &$cursor, $type, $field, $flags)
{
    $p =& $app->ui;
    $def =& $app->db->def;

    if (isset ($type['tk_autoform']))
        $conf = $type['tk_autoform'];
    else
        $conf = 0;

    # Print pop-up box with entries of a foureign table.
    if (isset ($conf['lookup'])) {
        $l =& $conf['lookup'];
        $table = $l['table'];
        $col = $l['field'];
        @$order = $l['order'];
        $p->select_id ($field, $table, $col, $def->primary ($table), $order);
        return;
    }

    # Print widget depending on extended data type. Default is an inputline.
    if (!isset ($type['e'])) {
        if ($flags & TK_AUTOFORM_NO_INPUT)
            $p->show ($field);
        else
            $p->inputline ($field, 40);
        return;
    }

    switch ($type['e']) {
        case 'boolean':
            $p->radiobox ($field, 'yes', 'no');
            break;

        case 'text':
            $val = $p->value ($field);
            $lines = substr_count ($val, "\n");

            if (!$lines)
                $lines = 1;
            $r = strlen ($val) / $lines;
            if ($r > 50)
                $lines = (int) ($lines * $r / 50);

            if ($lines < 5)
                $lines = 5;
            else {
                $lines += 2;
                if ($lines > 25)
                    $lines = 25;
            }
            $p->textarea ($field, 40, $lines);
            break;

        case 'show':
            $p->show ($type['n']);
            break;

        default:
          if ($flags & TK_AUTOFORM_NO_INPUT)
              $p->show ($field);
          else
              $p->inputline ($field, 40);
    }
}
 
/**
 * Create form from dbdepend description.
 *
 * @access public
 * @param object application $app
 * @param string $source Source name.
 */
function tk_autoform_create_form (&$app, $cursor)
{
    $def =& $app->db->def;
    $types = $def->types ($cursor->source ());

    foreach ($types as $field => $type)
        tk_autoform_create_widget ($app, $cursor, $type, $field, TK_AUTOFORM_LABELS);
}

function tk_autoform_list_cursor_field (&$app, &$c, &$conf, $field)
{
    global $lang;

    $p =& $app->ui;
    $def =& $app->db->def;
    $source = $c->source ();
    $pri = $def->primary ($source);

    $sdef = $def->definition ($source);
    $sdef = $sdef[$field];
    if (isset ($sdef['tk_auto']) && $sdef['tk_auto'] == 'hide')
        return;

    $p->open_cell ();
    $v = $c->value ($field);
    if (!$v)
        $v = $conf->txt_empty;
    $p->link ($v, new event ($conf->record_view, array ($conf->record_view_arg => $c->value ($pri))));
    $p->close_cell ();
}

class tk_auto_list_conf {
    var $record_view;
    var $record_view_arg;
    var $txt_empty;
}

/**
 * List cursor.
 *
 * @access public
 * @param object application $app
 * @param object cursor $c
 * @param array $config Configuration
 */
function tk_autoform_list_cursor (&$app, &$c, $conf)
{
    type ($app, 'application');
    type ($c, 'cursor');

    $p =& $app->ui;

    $index = 0;
    $p->open_context ($c);
    while ($c->get ()) {
        $index++;
        $p->open_row ();
        $p->label ("$index.");
        foreach ($app->db->def->field_names ($c->source ()) as $field)
            tk_autoform_list_cursor_field ($app, $c, $conf, $field);
        $p->close_row ();
    }
    $p->close_context ();

    $p->paragraph ();
    $p->print_text ("<b>Total: $index</b>");
}

?>

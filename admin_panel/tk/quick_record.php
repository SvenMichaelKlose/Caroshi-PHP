<?php

/**
 * Editor for SQL tables.
 *
 * This should work with containers. It actually doesn't work at all.
 *
 * @access public
 * @module tk_quick_record
 * @package User interface toolkits
 */

# Copyright (c) 2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

# Initialise the module.
function tk_quick_record_init (&$app)
{
    $app->add_function ('tk_quick_record');
}

# $app->args:
#	'source':    Name of source to edit
#	'selection': Parameter to get() for list.
#	'fields':    Fields to edit. (At the time an inputline).
function tk_quick_record (&$app)
{
    $args =& $app->args;
    $subargs =& $app->subargs;
    $ui =& $app->ui;
    $source = $app->arg ('source', ARG_SUB);
    $selection = $app->arg ('selection', ARG_SUB);

    $ui->headline ('Missing title');
    $ui->link ('zur&uuml;ck', 'return2caller');

    $ui->open_source ($source);
    if ($ui->query ()) {
        $ui->table_headers (array ('-', 'Missing header'));
        while ($p->get ()) {
            $ui->open_row ();

            $v =& new event ('record_delete');
            $v->set_next ($app->event);
            $ui->link ('Missing delete label', $v);

            foreach ($fields as $field)
                $ui->inputline ($field, 60);

	    $ui->close_row ();
        }
    } else
        $ui->label ('Missing label for empty table.');

    $ui->paragraph ();

    $ui->open_row ();

    $v =& new event ('record_create');
    $v->set_next ($app->event);
    $ui->link ('Missing create label.', $v);
    $v =& new event ('form_update');
    $v->set_next ($app->event);
    $ui->submit_button ('Ok', $v);

    $ui->close_row ();
    $ui->close_source ();
}

?>

<?php

/**
 * Standard event handler for context cursor manipulation.
 *
 * @access public
 * @module record
 * @package User interface
 */

# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


/**
 * Initialise this module.
 *
 * @param object application $app
 */
function _records_init (&$app)
{
    $h = array ('record_create', 'record_update', 'record_delete', 'record_delete_force');
    util_add_raw_functions ($app, $h);

    $app->record_messages = array (
        'record_saved' => 'Record saved.',
        'delete_ask' => 'Delete this record?',
        'delete_nothing' => 'Nothing delted.',
        'delete_done' => 'Record removed.',
        'create_done' => 'Record created.',
        'yes' => 'Yes.',
        'no' => 'No.'
    );
}

/**
 * Create records, print messagebox and jump to next view.
 *
 * @param object application $app
 * @param array Keyset created by record_create_set().
 * @see record_create_set()
 */
function _record_create_continue (&$app, $keys)
{
    $next_view = $app->arg ('next_view', ARG_OPTIONAL);
    $ret = $app->arg ('ret', ARG_OPTIONAL);
    $ui =& $app->ui;

    if (!$keys)
        die_traced ('Couldn\'t create record.');

    if ($keys && isset ($app->record_messages['create_done']))
        $ui->msgbox ($app->record_messages['create_done']);

    # Call next function.
    if ($next_view) {
        if (is_string ($next_view))
            $next_view = new event ($next_view);
            if ($ret) {
                $k = $keys;
                $k = array_pop ($k);
                reset ($k);
                $next_view->args[$ret] = $k[key ($k)];
            }
        $app->call ($next_view);
    }

    return $keys;
}

/**
 * Create record listed in $sources with optional field values or aliases.
 *
 * @access public
 * @param object application $app
 * @param array $set Source set which describes the records to be created.
 */
function record_create_set (&$app, &$set)
{
    $ui =& $app->ui;

    $keyset = array ();
    foreach ($set as $cursortype => $sources) {
        $tmp = "cursor_$cursortype";
        $cursor = new $tmp;

        foreach ($sources as $source => $pre) {
            if (!is_array ($pre))
                die_traced ("Can't create record. Preset values are not in an array for source $source.");

            # Replace aliases by key to inserted record in specified source in
            # this function. An alias is placed in preset_values' field data in
            # the form @<source name>.
            foreach ($pre as $field => $data) {
                if (substr ($data, 0, 1) == '@') {
                    $s = substr ($data, 1);
                    if (!isset ($aliases[$s]))
                        die_traced ("No record in source $source created source $s could point to.");

                    # Replace alias by id of inserted record.
                    $pre[$field] = $aliases[$s];
                }
            }

            # Create record.
            $cursor->set_source ($source);
            $key = $cursor->create ($pre);
            $keyset[$cursor->type ()][$source] = $key;

            # Remeber key for alias.
            $aliases[$source] = $key;

            # Highlight new record.
            $cursor->set_key ($key);
            $ui->highlight[$cursor->id ()] = '#00FF00';
        }
    }

    return $keyset;
}

/**
 * Event handler: Create a new row in table.
 *
 * Optional event argument 'sources' takes a source set, otherwise the
 * context cursor is used and field values can be specified in
 * array 'preset_values. 'msg' takes a message to print if the record
 * was created.
 *
 * @access public
 * @param object application $app
 * @see record_create_set()
 */
function record_create (&$app)
{
    $sources = $app->arg ('sources', ARG_OPTIONAL);
    $msg = $app->arg ('msg', ARG_OPTIONAL);
    $pre = $app->arg ('preset_values', ARG_OPTIONAL);

    # If link was created without arguments, create a source set with a
    # single element from the argument's cursor.
    if (!$sources) {
        $cursor = $app->arg ('_cursor');
        $sources[$cursor->type ()][$cursor->source ()] = $pre ? $pre : array ();
    } else
        if ($app->arg ('preset_values', ARG_OPTIONAL))
            die_traced ("Arguments 'preset_values' and 'sources' can't be used together.");

    $key = record_create_set ($app, $sources);
    return _record_create_continue ($app, $key);
}

/**
 * Calls form_update() and outputs a success or error message.
 *
 * Non-existing primary keys will be ignored. The event context is
 * highlighted.
 *
 * @access public
 * @param object application $app
 * @see cmd_update (), form_update()
 */
function record_update (&$app)
{
    $cursor = $app->arg ('_cursor');
    $ui =& $app->ui;

    $err = form_update ($app);
    if ($err)
        $ui->panic ($err);

    $ui->highlight[$cursor->id ()] = '#00FF00';
    $ui->msgbox ($app->record_messages['record_saved']);
}

/**
 * Event handler: Ask to delete records.
 *
 * This handler asks to kill records and then triggers record_delete_force().
 * Event argument '_cursor' can contain the context cursor, an array of
 * cursors can be passed in 'cursor_list' instead. 'yes_view' can contain
 * an event that is triggered if record were deleted, 'no_view' can contain
 * one for the opposite case (the deletion failed).
 *
 * @access public
 * @param object application $app
 * @see record_delete_force()
 */
function record_delete (&$app)
{
    $cursor = $app->arg ('_cursor', ARG_OPTIONAL);
    $cursor_list = $app->arg ('cursor_list', ARG_OPTIONAL);
    $yes_view = $app->arg ('yes_view', ARG_OPTIONAL);
    $no_view = $app->arg ('no_view', ARG_OPTIONAL);
    $ui =& $app->ui;
    $m = $app->record_messages;
    $tv = $app->event ();

    # Highlight records, check available cursors.
    if (!$cursor_list && !is_array ($cursor_list)) {
        if (!$cursor)
            die_traced ('No cursor.');
        if (!$cursor->key ())
            die_traced ("No key of any record in source '{${$cursor->source ()}}'.");

        # Highlight record.
        $ui->highlight[$cursor->id ()] = '#FFAAAA';
    } else {
        # Cancel deletion if cursor list is empty.
        if (!sizeof ($cursor_list))
            return;

        # Highlight records.
        foreach ($cursor_list as $cursor) {
            $cursor->set_field ('');
            $ui->highlight[$cursor->id ()] = '#FFAAAA';
        }
    }

    if (!$yes_view) {
        if (!$tv->next)
            die_traced ('No argument \'yes_view\' nor next view.');
        $yes_view = $tv->next;
    }
    if (!$no_view) {
        if (!$tv->next)
            die_traced ('No argument \'no_view\' nor next view.');
        $no_view = $tv->next;
    }

    # Create link to real kill.
    $yes = new event ('record_delete_force');
    $yes->set_next ($yes_view);
    $yes->set_arg ('_cursor', $app->arg ('_cursor', ARG_OPTIONAL));
    $yes->set_arg ('cursor_list', $cursor_list);

    $ui->confirm ($m['delete_ask'], $m['yes'], $yes, $m['no'], $no_view);
}

/**
 * Event handler: Delete records.
 *
 * Event argument '_cursor' can contain the context cursor, an array of
 * cursors can be passed in 'cursor_list' instead.
 *
 * @access public
 * @param object application $app
 */
function record_delete_force (&$app)
{
    $cursor = $app->arg ('_cursor', ARG_OPTIONAL);
    $cursor_list = $app->arg ('cursor_list', ARG_OPTIONAL);
    $ui =& $app->ui;

    if (!$cursor_list) {
        if (!$cursor)
            die_traced ('No context cursor or cursor_list.');

        $err = $cursor->delete ();
        if (!$err)
            return;
    } else {
        foreach ($cursor_list as $cursor)
            if ($err = $cursor->delete ())
                break;
    }

    if (isset ($err))
        $ui->panic ($err);

    $ui->msgbox ($app->record_messages['delete_done']);
}

?>

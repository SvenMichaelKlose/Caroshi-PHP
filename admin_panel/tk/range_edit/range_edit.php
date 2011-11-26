<?php
/**
 * Selection of multiple records (context cursors).
 *
 * @access public
 * @module tk_range_edit
 * @package User interface toolkits
 */

# Copyright (c) 2001-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

/**
 * Initialise the tooklit.
 *
 * @access public
 * @param object application $this
 */
function tk_range_edit_init (&$this)
{
    $h = array ('tk_range_edit_select', 'tk_range_edit_select_all', 'tk_range_edit_unselect_all', 'tk_range_edit_call');
    util_add_raw_functions ($this, $h);
}

/**
 * Get new selection from posted form.
 *
 * Event argument 'marker_field' contains the name for the form elements
 * that contain the markers. The content of markers is converted to boolean
 * values.
 *
 * @access public
 * @param object application $this
 */
function tk_range_edit_select (&$this)
{
    $marker_field = $this->arg ('marker_field');

    $ui =& admin_panel::instance ();

    # Collect markers of first source.
    $source = '';
    $markers = array ();
    $fetched_keys = array ();
    foreach ($this->elements as $e) {
        $cursor =& $e->cursor;
        $s = $cursor->source ();
        $k = $cursor->key ();

        if ($cursor->field () != $marker_field)
            continue;

        # Make sure we only handle one source.
        if (!$source)
            $source = $s; # Set source the first time.
        else
            if ($source != $s) # No other sources allowed.
                die ('tk_range_edit_select(): Markers from multiple sources.');

        if (!isset ($fetched_keys[$k])) {
            $fetched_keys[$k] = sizeof ($markers);
            $markers[] = $e;
        } else {
            $i = $fetched_keys[$k];
          $markers[$i] = $e;
        }
    }
    if (!isset ($markers))
        die ("tk_range_edit_select(): No element called '$marker_field' in form.");

    # Collect changed marker indices and markers that occured the first time.
    if (isset ($this->subargs['tk_range_edit'][$source])) {
        $old_markers = $this->subargs['tk_range_edit'][$source];
        foreach ($markers as $i => $marker) {
            $key = $marker->cursor->key ();
            $val = $marker->val;
            if (isset ($old_markers[$key]) && $old_markers[$key] != $val)
	        $changed[] = $i;
        }
    } else {
        # Since there're no old markers, take set ones as changed.
        #$i = 0;
        foreach ($markers as $i => $marker) {
            if ($marker->val)
	        $changed[] = $i;
            $i++;
        }
    }

    # Quit, if nothing changed.
    if (!isset ($changed))
        return;

    # Process each pair of changed markers.
    $record_cache =& $ui->record_cache;
    for ($i = 0; $i < sizeof ($changed); $i += 2) {
        # Invert markers between changed ones.
        $start = $changed[$i] + 1;
        if (!isset ($changed[$i + 1]))
            break;
        $end = $changed[$i + 1] - 1;
        while ($start <= $end) {
            $key = $markers[$start]->cursor->key ();
            if (isset ($record_cache[$source][$key][$marker_field]))
                $record_cache[$source][$key][$marker_field] ^= 1;
            else
	        #die ('tk_range_edit(): Need form filter form_safe() - stop.');
                $record_cache[$source][$key][$marker_field] = 1;
            $start++;
        }
    }

    # Highlight selected records and safe state pf markers to subsession..
    foreach ($markers as $marker) {
        $cursor =& $marker->cursor;
        $cursor->set_field ('');
        $source = $cursor->source ();
        $key = $cursor->key ();

        $this->subargs['tk_range_edit'][$source][$key] =
            $record_cache[$source][$key][$marker_field];

        if (isset ($record_cache[$source][$key][$marker_field]) && $record_cache[$source][$key][$marker_field])
            $ui->highlight[$cursor->id ()] = 'yellow';
    }

    record_cache_safe ($this);
}

/**
 * Check if all markers are selected.
 *
 * Event argument 'marker_field' contains the name for the form elements
 * that contain the markers. The content of markers is converted to boolean
 * values.
 *
 * @access public
 * @param object application $this
 * @returns int 0: No marker set, 1: All markers set, 2: Some markers set.
 */
function tk_range_edit_all_selected (&$this, $marker_field)
{
    $ui =& admin_panel::instance ();

    $s = $u = 0;
    if (!isset ($this->elements))
        return 0;

    foreach ($this->elements as $e) {
        $cursor =& $e->cursor;
        $source = $cursor->source ();
        $key = $cursor->key ();
        $field = $cursor->field ();

        if ($field == $marker_field) {
            if (isset ($ui->record_cache[$source][$key][$marker_field]) && $ui->record_cache[$source][$key][$marker_field])
                $s++;
            else
                $u++;
        }
    }
    if ($s && $u)
        return 2;
    if ($s)
        return 1;
    return 0;
}

/**
 * Select/unselect all markers.
 *
 * @access public
 * @param object application $this
 * @param string $marker_field Form names of marker fields.
 * @param boolean $val New content of marker fields.
 */
function tk_range_edit_do_select_all (&$this, $marker_field, $val)
{
    $ui =& admin_panel::instance ();

    foreach ($this->elements as $e) {
        $cursor =& $e->cursor;
        $src = $cursor->source ();
        $key = $cursor->key ();

        if ($cursor->field () == $marker_field)
            $ui->record_cache[$src][$key][$marker_field] = $val;
   }
}

/**
 * Event handler: Select all markers.
 *
 * Event argument 'marker_fields' contains the form name of all marker fields.
 *
 * @access public
 * @param object application $this
 */
function tk_range_edit_select_all (&$this)
{
    tk_range_edit_do_select_all (&$this, $this->arg ('marker_field'), true);
}

/**
 * Event handler: Unselect all markers.
 *
 * Event argument 'marker_fields' contains the form name of all marker fields.
 *
 * @access public
 * @param object application $this
 */
function tk_range_edit_unselect_all (&$this)
{
    tk_range_edit_do_select_all (&$this, $this->arg ('marker_field'), false);
}

/**
 * Event handler: Call a function with array of cursors where markers were
 * placed.
 *
 * Event argument 'marker_fields' contains the form name of all marker fields.
 * 'view' contains the name of the event handler to call and 'argname' its
 * argument name for the array of cursors.
 *
 * @access public
 * @param object application $this
 * @see cursor
 */
function tk_range_edit_call (&$this)
{
    $argname = $this->arg ('argname');
    $marker_field = $this->arg ('marker_field');
    $view = $this->arg ('view');

    $ui =& admin_panel::instance ();

    # Collect marker cursors into array.
    $cursors = array ();
    foreach ($this->elements as $e) {
        $cursor =& $e->cursor;
        $source = $cursor->source ();
        $key = $cursor->key ();
        $field = $cursor->field ();

        if ($field == $marker_field && isset ($ui->record_cache[$source][$key][$marker_field]) && $ui->record_cache[$source][$key][$marker_field])
            $cursors[] = $cursor;
    }

    $view->set_arg ($argname, $cursors);
    $this->call ($view);
}
?>

<?php
/**
 * Form parser and generic form handlers.
 *
 * @access public
 * @module form
 * @package User interface
 */

# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function _formviews_init (&$this)
{
    $e = array ('form_parser', 'form_update', 'form_create', 'form_safe', 'form_check');
    util_add_raw_functions ($this, $e);
}

/**
 * Collect and sort form elements and fetch uploaded files.
 *
 * @access private
 * @param object application $this Reference to current application.
 * @param array $forms            Form elements sorted by form index.
 * @param array $formevents       Form functions, keyed by form index.
 * @param array $filteredelements Elements that need filtering. 
 */
function _form_collect (&$this, &$forms, &$formevents, &$filteredelements)
{
    global $item;

    # Sort elements by form function and read the token data.
    if (!isset ($item) || !is_array ($item))
        die ('No form content to process.');

    $forms = array ();
    $formevents = array ();
    $filteredelements = array();
    foreach ($item as $token => $v) {
        # Unserialise _form_element object.
        $e =& $this->_tokens->get ($token);

        # Store form function specified by submit button.
        if ($e->is_submit) {
            # TODO: Multiple form functions (only first element is used).
            $formevents[$e->form_idx] = $e->view;
            continue;
        } else if (!isset ($formevents[$e->form_idx]) && $e->defaultfunc)
            $formevents[$e->form_idx] = $e->defaultfunc;

        # Get file upload.
        # See also $ui->filelink ().
        if ($field = $e->is_file) {
            # Skip entry if file info is incomplete.
            if (!isset ($GLOBALS[$field])
                || ($file = $GLOBALS[$field]) == 'none'
                || !($size = $GLOBALS[$field . '_size'])
                || !($name = $GLOBALS[$field . '_name']))
                continue;

            # Read in transmitted file.
            $f = fopen ($file, 'r');
            $v = fread ($f, $size);
            fclose ($f);

            $e->filename = $name;
            $e->file = $file;
        }

        $e->val = $v;

        # Schedule element for run through filter function.
        if ($e->use_filter) {
            $filter = $e->use_filter;
            $formfilter[$filter->name] =& $e;
            $filteredelements[$filter][$token] =& $e;
        }

        $forms[$e->form_idx][$token] =& $e;
    }
}

/**
 * Read in form, invoke form filters and trigger events.
 *
 * Forms without events are ignored.
 *
 * @access public
 * @oaram object application $this
 */
function form_parser (&$this)
{
    global $debug;
  
    # Read in form.
    _form_collect ($this, $forms, $formevents, $filteredelements);

    # Call each form filter with its set of elements.
    foreach ($filteredelements as $filter => $elements) {
        $this->elements =& $filteredelements[$filter];
        $this->call (new event ($filter));
    }

    # Trigger events in forms.
    foreach ($formevents as $index => $view) {
        if (!isset ($forms[$index]))
	    $forms[$index] = array ();

        # Sort in elements for form/event.
        unset ($this->elements);
        unset ($this->named_elements);
        unset ($this->element_sources);

        $this->elements =& $forms[$index];

        foreach ($this->elements as $k => $f) {
            $cursor =& $f->cursor;
            $source = $cursor->source ();
            $field = $cursor->field ();
            $v =& $f->val;

            # Run element through filter function.
            if ($f->element_filter_write) {
                $filter = $f->element_filter_write;
                $v = $filter ($v);
                $this->elements[$k]->val = $v;
            }

            # Save form value.
            if ($source && $v && $cursor->type ())
                $this->element_sources[$cursor->type ()][$source][$field] =& $v;

            # Sort in value for lookup by field name.
            $this->named_elements[$field] =& $f->val;
        }

        if ($debug) {
            echo "<b>Form elements: for $view->name:</b><br>";
            debug_dump ($this->elements);
        }

        $this->call ($view);
    }
}

/**
 * Write file type and name to record. 
 *
 * @oaram object application $this
 * @oaram object cursor $cursor
 */
function _form_update_fileinfo (&$this, &$cursor, &$e)
{
    # Get type and name of file.
    $field = $e->is_file;
    $name = $e->filename;

    # Update type column if any specified.
    if ($e->typefield) {
        $tmp = $cursor;
        $tmp->set_field ($e->typefield);
        $tmp->set ($type = magic2mime ($name));
    }

    # Update filename column if any specified.
    if ($e->filenamefield) {
        $tmp = $cursor;
        $tmp->set_field ($e->filenamefield);
        $tmp->set ($name);
    }
}


/**
 * Form handler: Write form content to record interface.
 *
 * Event argument 'keyset' (optional) takes a source set. Optional
 * argument 'ignored_elements' can contain an array of field names that are
 * ignored.
 *
 * @access public
 * @oaram object application $this
 */
function form_update (&$this)
{
    $keyset =& $this->arg ('keyset', ARG_OPTIONAL);
    $ignored =& $this->arg ('ignored_elements', ARG_OPTIONAL);

    $ui =& admin_panel::instance ();

    if (!isset ($this->elements))
        die ('form_update(): No form posted.');

    # Update form element by element.
    foreach ($this->elements as $token => $e) {
        $v = $e->val;
        $cursor =& $e->cursor;
        $src = $cursor->source ();
        $type = $cursor->type ();
        $field = $cursor->field ();

        # Continue if field name is in the list of ignored fields.
        if ($ignored && is_int (array_search ($field, $ignored)))
            continue;

        # If there's no key in the cursor, use the one in the keyset.
        if (!$cursor->key ()) {
            # Die if there's no key.
            if (!$keyset || !isset ($keyset[$type][$src]))
                die ("form_update(): No key for field '{${$cursor->field ()}}' in source '$src' of type '$type'.");

            $cursor->set_key ($keyset[$type][$src]);
        }

        $quote = false;

        # Also update name and type of file.
        if ($e->is_file) {
            _form_update_fileinfo ($this, $cursor, $e);
            $quote = true;
        }

        # Add slashes if not already done.
        if ($quote || !get_magic_quotes_gpc () && !get_magic_quotes_runtime ())
	    $v = addslashes ($v);

        # Update field in database.
        $cursor->set ($v);
    }
}

/**
 * Form filter: Write posted form to record cache.
 *
 * The record cache will identify records using the context cursor.
 *
 * @oaram object application $this
 */
function form_safe (&$this)
{
    $ui =& admin_panel::instance ();
    $record_cache =& $ui->record_cache;

    foreach ($this->elements as $e) {
        $cursor =& $e->cursor;
        $v =& $e->val;

        # Don't cache uploaded files.
        if ($e->is_file)
            break;

        $k = $cursor->key ();
        $key = $k ? $k : '_last';;
        $s = $cursor->source ();
        $f = $cursor->field ();
        if ($s && $f)
	    $record_cache[$s][$key][$f] = $v;
      }

      record_cache_safe ($this);
}

/**
 * Form handler: Create records with form content.
 *
 * Optional event argumet 'sources' takes a source set.
 *
 * @oaram object application $this
 */
function form_create (&$this)
{
    $sources =& $this->arg ('sources', ARG_OPTIONAL);

    $ui =& admin_panel::instance ();

    if (!form_has_content ($this)) {
        $ui->msgbox ('Record not created - fill form with content before.', 'red');
        return;
    }

    $sources = !isset ($sources) ?
               $this->element_sources :
               array_merge_recursive ($this->element_sources, $sources);
    $keys = record_create_set ($this, $sources);

    $arg = array ('keyset' => $keys);
    $this->call (new event ('form_update', $arg));

    return _record_create_continue ($this, $keys);
}

/**
 * Form handler: Typechecking of forms.
 *
 * Event argument 'patterns' must contain an array of arrays keyed by source
 * name.  * The sublevel arrays contain Perl-compatible regular expressions
 * (e.g. "/^\d+$/" for digits) keyed by field name.
 * If a field is not in the form, the pattern is ignored without notice.
 * Event argument 'on_error' takes an event that is triggered if a pattern
 * did not match. The erroraneous fields are highlighted.
 * In case a match contained an errror, warnings are printed and the
 * function dies after checking all fields.
 *
 * @oaram object application $this
 */
function form_check (&$this)
{
    $error_view = $this->arg ('on_error');
    $patterns = $this->arg ('patterns');
    $highlight_color = $this->arg ('highlight_color', ARG_OPTIONAL);
    if (!$highlight_color)
        $highlight_color = '#FF0000';

    global $debug;
    $ui =& admin_panel::instance ();

    # Check all form elements that come along.
    $errors = false;
    $panic = 0;
    foreach ($this->elements as $e) {
        $cursor =& $e->cursor;
        if ($cursor->type () != 'sql')
            continue;
        $source = $cursor->source ();
        $key = $cursor->key ();
        $field = $cursor->field ();
        $t = $def->types ($source);

        # Skip undefined elements.
        if (!isset ($patterns[$source][$field])) {
            # Warn in debug mode.
            if ($debug)
	        echo "<b>form_check(): Element '$field' not defined in source '$source'.</b><br>";
            continue;
        }
        $p = $pattern[$source][$field];

        # Continue if user-defined pattern matches.
        if ($e = preg_match ($e->val, $p))
            continue;

        if ($e === false) {
             echo "<b>form_check(): Pattern '$p' for field '$field' contains an error.";
             $panic++;
        }

        # Type is incorrect. Set highlighting for record.
        $ui->highlight[$cursor->id ()] = $highlight_color;
        $errors = true;
    }

    if ($panic)
        die ("$panic error(s) in form_check() configuration - stop.");

    # Continue with next function if there're no errors.
    if (!$errors)
        return;

    # Call error_view (error_args).
    $this->call ($error_view);

    # Avoid call of next function by exiting here.
    application::close ();
}

/**
 * Check if form contains any data.
 *
 * @oaram object application $this
 */
function form_has_content (&$this)
{
    if (!isset ($this->elements))
        die ('form_has_content(): Form function called without posted form.');

    foreach ($this->elements as $e)
        if (isset ($e->val) && $e->val)
	    return true;

    return false;
}

/**
 * Save record cache to session.
 *
 * @oaram object application $this
 */
function record_cache_safe (&$this)
{
    $session =& $this->session;
    $ui =& admin_panel::instance ();
    $rc =& $ui->record_cache;

    if (!isset ($ui->record_cache))
        return;

    $session->set ('_admin_panel.class/record cache', $rc);
}

/**
 * Fetch record cache from session. 
 *
 * @oaram object application $this
 */
function record_cache_fetch (&$this)
{
    $session =& $this->session;
    $ui =& admin_panel::instance ();
    $rc =& $ui->record_cache;

    $rc =& $session->get ('_admin_panel.class/record cache');
}
?>

<?php

# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once PATH_TO_CAROSHI . '/dbi/dbi.class.php';
require_once PATH_TO_CAROSHI . '/dbi/types.php';
require_once PATH_TO_CAROSHI . '/file/magic2mime.php';
require_once PATH_TO_CAROSHI . '/string/strhead.php';

require_once PATH_TO_CAROSHI . '/cursor/sql.class.php';
require_once PATH_TO_CAROSHI . '/admin_panel/formviews.php';
require_once PATH_TO_CAROSHI . '/admin_panel/mime.php';
require_once PATH_TO_CAROSHI . '/admin_panel/records.php';
require_once PATH_TO_CAROSHI . '/admin_panel/util.php';
require_once PATH_TO_CAROSHI . '/admin_panel/widgets.php';

require_once PATH_TO_CAROSHI . '/admin_panel/_view.class.php';
require_once PATH_TO_CAROSHI . '/admin_panel/_form_element.class.php';


/**
 * User interface
 *
 * @access public
 * @package User interface
 */
class admin_panel {

    /**
     * Reference to application that uses this instance.
     * @access public
     * @var object application
     */
    var $application;

    /**
     * Reference to widget set.
     * @access public
     * @var object widget_set
     */
    var $widgets;

    /**
     * Array of event handlers that open a document header or footer on
     * their own. The handler names are stores in the keys - the values can
     * contain anything (preferably a boolean).
     * @access public
     * @var array
     */
    var $raw_views;

    /**
     * Positions and colors of records to highlight. The array contains
     * HTML colors keyed by context cursor id.
     * @access public
     * @var array
     * @see cursor::id()
     */
    var $highlight;

    /**
     * Cached cursor values. The array contains the keyed by context cursor id.
     * @access public
     * @var array
     * @see value()
     */
    var $record_cache;

    /**
     * Reference to current context object.
     * @var object _admin_panel_view
     */
    var $v;

    /**
     * Don't generate a form in next open_source.
     * @access public
     * @var array
     */
    var $no_update = false;

    /**
     * Index of last form. The first form has index 1.
     * @var integer
     */
    var $_form_index = 0;

    /**
     * Name of form filter to save in created _form_element objects.
     * @var string
     * @see _new_formtoken()
     */
    var $_form_filter = '';

    /**
     * Name of element filter for value reads.
     * @var string
     * @see value()
     */
    var $_element_filter_read = '';

    /**
     * Name of element filter for value writes.
     * @var string
     * @see form_parser(),_new_formtoken()
     */
    var $_element_filter_write = '';

    /**
     * Context stack.
     * @var array
     * @see open_context(), close_context()
     */
    var $_viewstack;

    /**
     * Number of opened forms.
     * @var integer
     */
    var $_openform = 0;

    /**
     * Number of open tables.
     * @var integer
     */
    var $_opentable = 0;

    /**
     * Number of open rows.
     * @var integer
     */
    var $_openrow = 0;

    /**
     * Number of open cells.
     * @var integer
     */
    var $_opencells = 0;

    /**
     * Use anchors in links when set to 'true'.
     * @var boolean
     */
    var $_anchor = false;

    /**
     * Number of set anchors.
     * @var boolean
     */
    var $_anchors = 0;

    ################################
    ### Construction/destruction ###
    ################################

    /**
     * Initialise everything.
     *
     * @access public
     * @param object application $app
     * @param object widget_set $widgets
     */
    function admin_panel (&$app, $widgets = 0)
    {
        type ($app, 'application');
        $widgets && type_object ($widgets);

        # Initialize member variables.
        $this->application =& $app;
        $this->application->ui =& $this;
        $this->application->short_links = false;
        $this->widgets = $widgets ? $widgets : new widget_set ();
        $this->db =& $app->db;
        $this->highlight = array ();
        $this->_viewstack = array ();
        $app->raw_views['__return_mime'] = true;

        record_cache_fetch ($app);

        # Initialize SQL cursors.
        cursor_sql::set_db ($this->db);

        $this->v = new _admin_panel_view (new cursor_sql (), null, null);

        # Init modules.
        _formviews_init ($app);
        _records_init ($app);
        _mime_init ($app);
    }

    /**
     * Finish HTML document and exit.
     *
     * @access public
     */
    function close ()
    {
        $app =& $this->application;
        $source = $this->v->cursor->source ();

        # Check if all data source contexts are closed.
        if ($openviews = sizeof ($this->_viewstack))
	    die_traced ("$openviews view(s) still open for source '$source' - stop.");

        # Call user defined function or output standard footer or do nothing.
        if (!method_exists ($app, 'end_view')) {
            $tv = $app->event ();
            if (!isset ($app->raw_views[$tv->name]))
                $this->widgets->footer ();
        }

        # Save record cache to session data.
        record_cache_safe ($this->application);
    }


    #############
    ### Views ###
    #############

    /**
     * Set a new context.
     *
     * Overwrites the current context.
     *
     * @access public
     * @param object cursor $cursor
     */
    function set_context (&$cursor)
    {
        type ($cursor, 'cursor');
        $this->v = new _admin_panel_view ($cursor, $this->no_update, null);
    }

    /**
     * Open a new context.
     *
     * Saves the former context before setting the new one.
     *
     * @access public
     * @param object cursor $cursor
     */
    function open_context (&$cursor)
    {
        type ($cursor, 'cursor');

        if (!sizeof ($this->_viewstack))
            $this->_form_index++;

        array_push ($this->_viewstack, $this->v);
        $this->set_context ($cursor);
    }

    /**
     * Return to former context.
     *
     * @access public
     */
    function close_context ()
    {
        $this->v = array_pop ($this->_viewstack);
    }

    /**
     * Get context cursor.
     *
     * @access public
     * @returns object cursor
     */
    function cursor ()
    {
        return $this->v->cursor;
    }

    /**
     * Set context cursor.
     *
     * @access public
     * @param object cursor $x
     */
    function set_cursor (&$x)
    {
        return $this->v->cursor =& $x;
    }

    /**
     * Set form filter function.
     *
     * Filters are used indepently from the context.
     *
     * @access public
     * @param string $filtername Name of the filter function.
     */
    function use_filter ($filtername)
    {
        type_string ($filtername);

        $this->_form_filter = $filtername;
    }

    /**
     * Set element filter function.
     *
     * The filters are used for all following form elements. Pass empty
     * strings ('') if you don't want any filtering.
     *
     * @access public
     * @param string $filtername Name of the filter function.
     */
    function use_element_filters ($filter_read, $filter_write)
    {
        type_string ($filter_read);
        type_string ($filter_write);

        $this->_element_filter_read = $filter_read;
        $this->_element_filter_write = $filter_write;
    }

    ########################
    ### Record interface ###
    ########################

    /**
     * Get the cursor used in the current context.
     *
     * @access public
     * @returns object cursor
     */
    function &get_cursor ()
    {
        return $this->v->cursor;
    }

    /**
     * Clear the record cache.
     *
     * @access public
     */
    function clear_record_cache ()
    {
        unset ($this->record_cache);
    }

    /**
     * Get a field from a record the current context points to.
     *
     * If there's a value stored in the record cache it is returned instead.
     *
     * @access public
     * @param string $field_name
     */
    function value ($field_name)
    {
        type_string ($field_name);

        $v =& $this->v;
        $cursor =& $v->cursor;
        $record_cache =& $this->record_cache;
        $f =& $this->_element_filter_read;

        if ($cursor->source ())
            $source = $cursor->source ();
        $key = $cursor->key () ? $cursor->key () : '_last';

        # Check if there's something in the cache.
        if (isset ($source)) {
            $i =& $record_cache[$source][$key][$field_name];
	        if (isset ($i))
                return $i;
        }

        # Return real records returned by the record interface.
        $r = $cursor->current ();
        $i =& $r[$field_name];

        # Run element through filter function.
        if ($f)
            $i = $f ($i);

        return $i;
    }

    /**
     * Set a field in the record cache.
     *
     * @access public
     * @param string $field Field name.
     * @param mixed $val    New value to set.
     */
    function set_value ($field, $val)
    {
        type_string ($field);

        $v =& $this->v->cursor;
        $this->record_cache[$v->source ()][$v->key ()][$field] = $val;
    }

    /**
     * Print document header.
     *
     * @access public
     * @param string $title
     * @param string $comp  Copyright and/or version info.
     */
    function header ($header, $comp = '')
    {
        $this->widgets->header ($header, $comp);
    }

    #########################
    ### Widget generation ###
    #########################

    /**
     * Print a headline.
     *
     * @access public
     * @param string $text
     */
    function headline ($text)
    {
        $this->widgets->headline ($text);
    }

    /**
     * Print an informal message box.
     *
     * @access public
     * @param string $message
     * @param string $color   Color in HTML attribute format.
     */
    function msgbox ($message, $color = 0)
    {
        $this->open_widget ();
        $this->widgets->msgbox ($message, $color);
        $this->close_widget ();
    }

    /**
     * Print error message and die.
     *
     * @access public
     * @param string $message
     */
    function panic ($message)
    {
        global $SERVER_ADMIN;

        echo "<P><FONT COLOR=RED><B>$message";
        if (!isset ($SERVER_ADMIN))
            $SERVER_ADMIN = 'root';
        mail ($SERVER_ADMIN, "Panic: $SERVER_NAME", "$message\n");
        echo '<P>Internal script error. Administrator alarmed.';
        echo '</B></FONT>';
        echo '</BODY></HTML>';
        die ($message);
    }

    /**
     * Print confirmation box.
     *
     * @access public
     * @param string $msg Question to the user.
     * @param string $option_yes Label for confirming option.
     * @param object event $event_yes Event to trigger for confirming option.
     * @param string $option_no Label for non-confirming option.
     * @param object event $event_no Event to trigger for non-confirming option.
     * @param string $color Color for question in HTML attribute format.
     */
    function confirm ($msg, $option_yes, $event_yes, $option_no, $event_no, $color = 0)
    {
        type ($event_yes, 'event');
        type ($event_no, 'event');

        $this->widgets->msgbox ($msg, $color);

        $t = "<FONT COLOR=\"GREEN\">$option_no</FONT>";
        $l = $this->_looselink ($t, $event_no);
        $this->widgets->msgbox ($l);

        $t = "<FONT COLOR=\"RED\">$option_yes</FONT>";
        $l = $this->_looselink ($t, $event_yes);
        $this->widgets->msgbox ($l);
    }

    /**
     * Set default form event for current context.
     *
     * @access public
     * @param object event $event
     */
    function set_default_formevent ($event)
    {
        type ($event, 'event');

        $this->v->defaultfunc =& $event;
    }

    /**
     * Open form that is posted to form parser form_parser().
     *
     * @access public
     * @param object event $default_event
     *        Default form event used when form is posted without submit
     *        button.
     */
    function open_form ($default_event = 0)
    {
        $default_event && type ($default_event, 'event');

        if ($default_event)
            $this->set_default_formevent ($default_event);

        # Only open a document form if we are in need for updates and there's no
        # already opened form.
        if (!$this->no_update || !$this->_openform)
            $this->widgets->open_form ($this->url (new event ('form_parser')));

        $this->_openform++;
    }

    /**
     * Close an opened form.
     *
     * This function dies if there's no opened form.
     *
     * @access public
     */
    function close_form ()
    {
        if (!$this->_openform--)
            die_traced ('No form opened - stop.');

        if (!$this->_openform)
            $this->widgets->close_form ();
    }

    /**
     * Open a table.
     *
     * @access public
     */
    function open_table ($attrs = 0)
    {
        $attrs && type_array ($attrs);

        if (!$this->_opentable++)
            $this->widgets->open_table ($attrs);
    }

    /**
     * Close an opened table.
     *
     * This function dies if there's no opened table.
     *
     * @access public
     */
    function close_table ()
    {
        if (!$this->_opentable--)
            die_traced ('Table stack underflow - stop.');
        if ($this->_opentable)
            return;

        if ($this->_openrow)
            die_traced ("$this->_openrow rows still open.");
        if ($this->_opencells)
            die_traced ("$this->_opencells cells still open.");

        $this->widgets->close_table ();
    }

    /**
     * Set color for a cell if the current cursor position is highlighted.
     *
     * @access private
     */
    function _do_highlighting (&$attrs)
    {
        if (!is_array ($attrs))
            $attrs = array ();
        $cursor =& $this->v->cursor;
        $vid = $cursor->id ();
        $f = $cursor->field ();
        $h =& $this->highlight;
        $w =& $this->widgets;

        # Do highlighting.
        if (isset ($h[$vid]))
  	    $attrs['BGCOLOR'] = $h[$vid];
        else if (isset ($f) && isset ($h[$vid . $f]))
  	    $attrs['BGCOLOR'] = $h[$vid . $f];

        # Else use default color.
        else if (!isset ($attrs['BGCOLOR']) && isset ($w->color['cell'])) 
  	    $attrs['BGCOLOR'] = $w->color['cell'];
    }

    /**
     * Open a row.
     *
     * @access public
     */
    function open_row ($attrs = 0)
    {
        $attrs && type_array ($attrs);

        $this->_do_highlighting ($attrs);

        if (!$this->_openrow++)
            $this->widgets->open_row ($attrs);
    }

    /**
     * Close an opened row.
     *
     * This function dies if there's no opened row.
     *
     * @access public
     */
    function close_row ()
    {
        if (!$this->_openrow--)
            die_traced ('Table row stack underflow - stop.');
        if ($this->_openrow)
            return;
        if ($this->_opencells)
            die_traced ("$this->_opencells cells still open.");

        $this->widgets->close_row ();
    }

    /**
     * Open a cell.
     *
     * @access public
     */
    function open_cell ($attrs = 0)
    {
        $w =& $this->widgets;

        $attrs && type_array ($attrs);

        if ($this->_opencells++)
            return;
        if (!$this->_openrow)
            die_traced ('No row for cell.');

        $this->_do_highlighting ($attrs);

        $w->open_cell ($attrs);
    }

    /**
     * Close an opened cell.
     *
     * This function dies if there's no opened cell.
     *
     * @access public
     */
    function close_cell ()
    {
        if (--$this->_opencells)
            return;
        if ($this->_opencells < 0)
            die_traced ('Table cell stack underflow - stop.');
        $this->widgets->close_cell ();
    }

    /**
     * Create table header from array of strings.
     *
     * @access public
     * @param array $titles Column titles.
     */
    function table_headers ($titles, $attrs = 0)
    {
        type_array ($titles);
        $attrs && type_array ($attrs);

        $this->widgets->table_headers ($titles, $attrs);
    }
    
    /**
     * Print contents of a record's field.
     *
     * @access public
     * @param string $field Field name.
     */
    function show ($field)
    {
        type_string ($field);

        $text = $this->value ($field);
        if (!$text)
	        $text = '&nbsp;';

        $this->open_widget ($field);
        $this->widgets->print_text ($text);
        $this->close_widget ();
    }
    
    /**
     * Create cell with contents of row in a referenced table.
     *
     * @access public
     * @param string $field
     *        Name of field that references the source.
     * @param string $source
     *        Name of table that is references.
     * @param string $column
     *        Name of the column in referenced table that is printed.
     */
    function show_ref ($field, $source, $column)
    {
        type_string ($field);
        type_string ($source);
        type_string ($column);

        $val = $this->db->column ($source, $column, $this->value ($field));
        $this->open_widget ($field);
        $this->widgets->print_text ($val);
        $this->close_widget ();
    }

    /**
     * Print a paragraph.
     *
     * A paragraph reopens a table so one can start with a new number of
     * columns. Outside tables a paragraph is printed.
     *
     * @access public
     */
    function paragraph ($html = '')
    {
        if ($this->_opentable) {
            $o = $this->_opentable;
            while ($this->_opentable)
                $this->close_table ();
            echo $html;
            while ($o--)
                $this->open_table ();
        }
    }

    /**
     * Open a row and cell for a widget and set the current field.
     *
     * The field name is used for highlighting.
     *
     * @access public
     * @param string $field Currently accessed field.
     * @param array $attr
     *        HTML attributes for the opened cell, keyed by attribute name.
     */
    function open_widget ($field = '', $attrs = 0)
    {
        type_string ($field);
        $attrs && type_array ($attrs);

        $c =& $this->v->cursor;

        $c->set_field ($field);
        $this->open_row ();
        $this->open_cell ($attrs);
    }

    /**
     * Close opened call and row.
     *
     * The field name for highlighting is reset.
     *
     * @access public
     */
    function close_widget ()
    {
        $c =& $this->v->cursor;

        $this->close_cell ();
        $this->close_row ();
        $c->set_field ('');
    }

    ### The following functions only work inside tables:

    /**
     * Print text.
     *
     * Instead of php's echo function print_text() should be used so the
     * widget set can control the layout.
     *
     * @access public
     * @param string $text
     */
    function print_text ($text)
    {
        if (!$text)
	        $text = '&nbsp;';

        $this->open_widget ();
        $this->widgets->print_text ($text);
        $this->close_widget ();
    }

    /**
     * Print label.
     *
     * This function prints a text which looks different to that
     * generated by print_text() amd should have a descriptive character.
     *
     * @access public
     * @param string $text
     */
    function label ($text)
    {
        if (!$text)
	        $text = '&nbsp;';

        $this->open_widget ();
        $this->widgets->print_label ($text);
        $this->close_widget ();
    }

    /**
     * Print image.
     *
     * @access public
     * @param string $field Current record's field that contains the image.
     * @param string $type MIME type of the image.
     */
    function show_mime_image ($field, $type)
    {
        type_string ($field);
        type_string ($type);

        $v =& $this->v->cursor;
        $source = $v->source ();
        $url = $this->fileurl ($source, $field, $v->key (), $type, $this->value ($field));

        $this->open_widget ($field);
        $this->widgets->image ('', $url);
        $this->close_widget ();
    }

    function _inputline ($type, $field, $maxlen)
    {
        $w =& $this->widgets;
        $size = ($maxlen < 60) ? $maxlen : 60;
        $val = htmlentities ($this->value ($field));

        $this->open_widget ($field);
        if ($type == 'TEXT')
            $w->inputline ($this->new_formfield ($field), $val, $size);
        else
            $w->password ($this->new_formfield ($field), $val, $size);
        $this->close_widget ();
    }

    /**
     * Print an input line.
     *
     * @access public
     * @param string $field
     * @param integer $maxlen Width of line in number of characters.
     */
    function inputline ($field, $maxlen)
    {
        type_string ($field);
        type_int ($maxlen);

        $this->_inputline ('TEXT', $field, $maxlen);
    }


    /**
     * Print a password line.
     *
     * The content of the line is never shown.
     *
     * @access public
     * @param string $field
     * @param integer $maxlen Width of line in number of characters.
     */
    function password ($field, $maxlen, $label = '')
    {
        type_string ($field);
        type_int ($maxlen);

        $this->_inputline ('PASSWORD', $field, $maxlen);
    }

    /**
     * Print boolean radio box.
     *
     * @access public
     * @param string $field
     * @param string $label_true Label text for 'true' option.
     * @param string $label_false Label text for 'false' option.
     * @param mixed $value_true Data for 'true' option.
     * @param mixed $value_false Data for 'false' option.
     */
    function radiobox ($field, $label_true, $label_false, $value_true = 1, $value_false = 0)
    {
        type_string ($field);

        $w =& $this->widgets;
        $v = $this->value ($field);
        $i = $this->new_formfield ($field);

        $this->open_widget ($field);
        $w->radiobox ($i, $value_true, $v);
        echo $label_true;
        $w->radiobox ($i, $value_false, $v);
        echo $label_false;
        $this->close_widget ();
    }

    /**
     * Select a string
     *
     * @access public
     * @param string $field
     * @param array $optionlist Strings to select from.
     * @param booleanean $use_stringkey
     *        If this is true, the array key of the selected option is
     *        stored in the field.
     */
    # Select one of the strings in array $optionlist.
    function select_string ($field, $optionlist, $use_stringkey = true)
    {
        type_string ($field);
        type_array ($optionlist);

        if ($use_stringkey) {
            foreach ($optionlist as $string)
                $options[$string] = $string;
        } else
            $options = $optionlist;

        $this->open_widget ($field);
        $this->widgets->select ($this->new_formfield ($field), $this->value ($field), $options);
        $this->close_widget ();
    }

    /**
     * Select an entry in a foureign table and store its primary key.
     *
     * @access public
     * @param string $field
     * @param string $source Name of foureign table.
     * @param string $column Name of column in foureign table that is printed.
     * @param string $id Name of the primary key column.
     * @param string $where
     *        WHERE clause to use to select the records from the foureign
     *        table (without WHERE keyword).
     */
    function select_id ($field, $source, $column, $id, $where = '')
    {
        type_string ($field);
        type_string ($source);
        type_string ($column);

        $options[0] = '-';
        $res = $this->db->select ("$column,$id", $source, '', $where);
        while ($res && $row = $res->get ())
            $options[$row[$id]] = $row[$column];

        $this->open_widget ($field);
        $this->widgets->select ($this->new_formfield ($field), $this->value ($field), $options);
        $this->close_widget ();
    }

    /**
     * Print textarea.
     *
     * @access public
     * @param string $field
     * @param integer $width Number of columns.
     * @param integer $height Number of rows.
     */
    function textarea ($field, $width, $height)
    {
        type_string ($field);
        type_int ($width);
        type_int ($height);

        $val = htmlentities ($this->value ($field));

        $this->open_widget ($field);
        $this->widgets->textarea ($this->new_formfield ($field), $width, $height, $val);
        $this->close_widget ();
    }

    /**
     * Print file upload widget.
     *
     * @access public
     * @param string $field
     * @param string $typefield
     *        Name of field that will contain the uploaded file's MIME type.
     * @param string $filenamefield
     *        Name of field that will contain the uploaded file's original
     *        file name.
     */
    function fileform ($field, $typefield = '', $filenamefield = '')
    {
        type_string ($field);
        type_string ($typefield);
        type_string ($filenamefield);

        $w =& $this->widgets;

        # Store type and fieldnames.
        $f = new _form_element;
        $filefield = $this->application->_tokens->create (array ('dummy'));
        $f->is_file = $filefield;	# File data element.
        $f->typefield = $typefield; # File type record field..
        $f->filenamefield = $filenamefield; # File name record field.

        $this->open_widget ($field);
        $w->hidden ($this->new_formfield ($field, 0, $f), 0);
        $w->fileform ($filefield);
        $this->close_widget ();
    }

    /**
     * Print a checkbox.
     *
     * Posting a checkbox will result in value 0 or 1 for the field.
     *
     * @access public
     * @param string $field
     * @param object event $event This is probably unused...
     */
    function checkbox ($field, $event = 0)
    {
        type_string ($field);
        $event && type ($event, 'event');

        $w =& $this->widgets;

        $this->open_widget ($field);

        # Workaround which forces a value of 0 if the checkbox is not selected.
        $w->hidden ($this->new_formfield ($field, $event), 0);

        # Print checkbox.
        $w->checkbox ($this->new_formfield ($field, $event), 1, $this->value ($field));

        $this->close_widget ();
    }

    /**
     * Print a reset button.
     *
     * @access public
     * @param string $label Label for the button.
     */
    function reset_button ($label = 'reset')
    {
        $this->open_widget ('', array ('ALIGN' => 'CENTER'));
        $this->widgets->reset ($label);
        $this->close_widget ();
    }

    /**
     * Get name for a submit button.
     *
     * @access public
     * @param object event $event Event to use for the submit button.
     * @returns string Form element name.
     */
    function submit_name ($event = 0)
    {
        $f = new _form_element;
        $f->is_submit = true;

        return $this->_new_formtoken ($event, $f);
    }

    /**
     * Print a submit button.
     *
     * @access public
     * @param string $label Label for the button.
     * @param object event $event Event to use for the submit button.
     */
    function submit_button ($label, $event)
    {
        $f = new _form_element;
        $f->is_submit = true;

        $this->open_widget ('', array ('ALIGN' => 'CENTER'));
        $this->widgets->submit ($this->_new_formtoken ($event, $f), $label);
        $this->close_widget ();
    }

    /**
     * Print a submit image.
     *
     * @access public
     * @param string $label Label for the button.
     * @param string $image URL to image.
     * @param object event $event Event to use for the submit button.
     */
    function submit_image ($label, $image, $view = 0)
    {
        $f = new _form_element;
        $f->is_submit = true;
        $formname = $this->_new_formtoken ($view, $f);

        $this->open_widget ('', array ('ALIGN' => 'CENTER'));
        $this->widgets->submit_image ($label, $image, $formname);
        $this->close_widget ();
    }

    #############
    ### Links ###
    #############

    ### This functions also work outside of views:
 
    /**
     * Switch on use of anchors.
     *
     * If a link points to the current event handler, an anchor is used
     * to scroll to the position of the triggered link.
     *
     * @access public
     */
    function use_anchor ()
    {
        $this->_anchor = true;
        $this->_anchors++;
        echo "<A NAME=\"a$this->_anchors\">";
    }

    /**
     * Switch off use of anchors.
     *
     * @access public
     */
    function no_anchor ()
    {
        $this->_anchor = false;
    }

    # Create HTML-Link without table tags.
    # The link is not printed but returned! Echo yourself.
    function _looselink ($label, $view, $fakename = '')
    {
        $url = $this->url ($view);

        return "<A HREF=\"$url$fakename\">$label</A>";
    }

    /**
     * Print a link.
     *
     * @access public
     * @param string $label A text for the link.
     * @param object event $event
     *        The event that is triggered when the link is invoked.
     * @param string $fakename A fake filename for file downloads.
     */
    function link ($label, $event, $fakename = '')
    {
        if (is_string ($event))
            $event = new event ($event);
        type ($event, 'event');
        $fakename && type_string ($fakename);

        if (!$this->_opentable) {
            echo '[' . $this->_looselink ($label, $event, $fakename) . '] ';
	    return;
        }
        $link = $this->_looselink ($label, $event, $fakename);

        $this->open_widget ();
        $this->widgets->print_text ($link);
        $this->close_widget ();
    }

    /**
     * Print an image.
     *
     * @access public
     * @param string $label A text for the link.
     * @param string $src URL to the image.
     */
    # Create image cell with link.
    function image ($label, $src)
    {
        $this->open_widget ();
        $this->widgets->image ($label, $src);
        $this->close_widget ();
    }

    /**
     * Print an image link.
     *
     * @access public
     * @param string $label A text for the link.
     * @param string $src URL to the image.
     * @param object event $event
     *        The event that is triggered when the link is invoked.
     */
    # Create image cell with link.
    function image_link ($label, $src, $event)
    {
        type_string ($label);
        type_string ($src);
        type ($event, 'event');

        $url = $this->url ($event);

        $this->open_widget ();
        $this->widgets->image_link ($label, $src, $url);
        $this->close_widget ();
    }

    ###############################
    ### URLs & form field names ###
    ###############################

    /**
     *
    # Create a url of a file.
     *
     * @access public
     * @param string $source Source/table name of the file.
     * @param string $field Field where the file is stored.
     * @param string $type MIME type of the file.
     * @param string $key Primary key of the record.
     */
    function fileurl ($source, $field, $key, $mime_type, $data = 0)
    {
        $pri = $this->db->def->primary ($source);
        $arg = array ('source' => $source, 'column' => $field,
	                  'primary' => $pri, 'key' => $key, 'type' => $mime_type);
        return $this->application->link ('__return_mime', $arg) .
               md5 (substr ($data, 0, 1024)) .
               '.' . substr ($mime_type, strpos ($mime_type, '/') + 1);
    }

    /**
     * Create URL from event object.
     *
     * This function must be used instead of the application class functions.
     *
     * @access public
     * @param object event $event Event to trigger if URL is invoked.
     */
    function url (&$event)
    {
        $c =& $this->v->cursor;
        if (is_string ($event))
            $event = new event ($event);
        else
            type ($event, 'event');

        # Create a link.
        if (!$event->arg ('_cursor'))
            $event->set_arg ('_cursor', $c); # Add context.
        $link = $this->application->link ($event);

        # Add anchor if activated and the link points to this event handler.
        if ($this->_anchor)
	        $link .= "#a$this->_anchors";

        return $link;
    }

    /**
     * Create form name for a field in a particular record.
     *
     * @access public
     * @param string $field
     * @param object event $event Event to trigger if widget is a submit button.
     * @param object _form_element $f For internal use.
     */
    function new_formfield ($field, $event = 0, $element = 0)
    {
        $event && type ($event, 'event');

        $c = $this->v->cursor;

        $c->set_field ($field);
        if (!$element)
            $element = new _form_element;
        $element->cursor = $c;

        return $this->_new_formtoken ($event, $element);
    }

    /**
     * Create new name for a form element.
     *
     * This function creates a name containing a token
     *
     * @param object event $event
     * @param object _form_element $element
     */
    function _new_formtoken ($event, $element)
    {
        if ($event) {
            $event->subsession = $this->application->event ()->subsession;
            $element->view = $event;
        }

        $df =& $this->v->defaultfunc;
        if (isset ($df))
	        $element->defaultfunc = $df;

        $element->use_filter = $this->_form_filter;
        $element->element_filter_write = $this->_element_filter_write;

        $fi = $this->_form_index;
        $element->form_idx = $fi ? $fi : 0;

        if (!$element->cursor)
            $element->cursor = $this->v->cursor;

        if ($element->view)
            $element->view->set_arg ('_cursor', $element->cursor);

        return 'item[' . $this->application->_tokens->create ($element) . ']';
    }
}

?>

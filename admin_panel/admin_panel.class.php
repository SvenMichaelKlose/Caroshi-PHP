<?php
# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once 'dbi/dbi.class';
require_once 'dbi/types.php';
require_once 'file/magic2mime.php';
require_once 'object/singleton.class.php';
require_once 'string/strhead.php';

require_once 'cursor/sql.class.php';
require_once 'admin_panel/formviews.php';
require_once 'admin_panel/mime.php';
require_once 'admin_panel/records.php';
require_once 'admin_panel/util.php';
require_once 'admin_panel/widgets.php';

require_once 'admin_panel/_view.class.php';
require_once 'admin_panel/_form_element.class.php';

/**
 * User interface
 *
 * @access public
 * @package User interface
 */
class admin_panel extends singleton {

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
     * @see val()
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
        if (!is_a ($app, 'application'))
            die ('admin_panel constructor: First argument is not an application ' .
                 'object.');
        if ($widgets && !is_object ($widgets))
            die ('admin_panel constructor: Widget set is not an object.');

        $this->singleton ($this);

        # Initialize member variables.
        $this->application =& $app;
        $this->application->short_links = false;
        $this->widgets = $widgets ? $widgets : new widget_set ();
        $this->db =& $app->db;
        $this->v = new _admin_panel_view;
        $this->highlight = array ();
        $this->_viewstack = array ();
        $app->raw_views['__return_mime'] = true;

        record_cache_fetch ($app);

        # Initialize SQL cursors.
        cursor_sql::set_db ($this->db);
        $this->v->cursor =& new cursor_sql ();

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
	    die ("admin_panel::close(): $openviews view(s) still open for " .
                 "source '$source' - stop.");

        # Call user defined function or output standard footer or do nothing.
        if (!method_exists ($app, 'end_view')) {
            $tv = $app->event ();
            if (!isset ($app->raw_views[$tv->name]))
                $this->widgets->footer ();
        }

        # Save record cache to session data.
        record_cache_safe ($this->application);
    }

    /**
     * Get reference to the one and only instance of this class.
     *
     * @access public
     * @returns object admin_panel
     */
    function &instance ()
    {
        return singleton::instance ('admin_panel');
    }

    #############
    ### Views ###
    #############

    /**
     * Open a new context.
     *
     * @access public
     * @param object cursor $cursor
     */
    function open_context (&$cursor)
    {
        if (!is_a ($cursor, 'cursor'))
            die ('admin_panel::open_context(): Argument is not a cursor');
        if (!sizeof ($this->_viewstack))
            $this->_form_index++;

        # Push old view on stack.
        array_push ($this->_viewstack, $this->v);

        # Initialise view.
        $v =& new _admin_panel_view;
        $v->no_update = $this->no_update;
        $v->cursor =& $cursor;
        $this->v =& $v;
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
     * Set form filter function.
     *
     * Filters are used indepently from the context.
     *
     * @access public
     * @param string $filtername Name of the filter function.
     */
    function use_filter ($filtername)
    {
        if (!is_string ($filtername))
            die ('admin_panel::use_filter(): Argument is not a string');

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
        if (!is_string ($filter_read))
            die ('admin_panel::use_element_filters(): Argument 1 is not a string');
        if (!is_string ($filter_read))
            die ('admin_panel::use_element_filters(): Argument 2 is not a string');

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
        $v =& $this->v;
        $cursor =& $v->cursor;
        $record_cache =& $this->record_cache;
        $f =& $this->_element_filter_read;

        if (!is_string ($field_name))
            die ('admin_panel::value(): Argument is not a string');

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

        if (!is_null ($i))
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
        $v =& $this->v->cursor;

        if (!is_string ($field_name))
            die ('admin_panel::set_value(): Field name is not a string');

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
        $this->open_widget ();
        $this->widgets->headline ($text);
        $this->close_widget ();
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
    function confirm ($msg, $option_yes, $event_yes, $option_no, $event_no,
                      $color = 0)
    {
        if (!is_a ($event_yes, 'event'))
            die ('admin_panel::confirm(): event_yes is not an event object.');
        if (!is_a ($event_no, 'event'))
            die ('admin_panel::confirm(): event_no is not an event object.');

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
        if (!is_a ($event, 'event'))
            die ('admin_panel::set_default_formevent(): Argument is not an event.');

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
        if ($default_event && !is_a ($default_event, 'event'))
            die ('admin_panel::open_form(): Argument is not an event.');

        if ($default_event)
            $this->set_default_formevent ($default_event);

        # Only open a document form if we are in need for updates and there's no
        # already opened form.
        $nu =& $v->no_update;
        if (!$this->_openform && (!isset ($nu) || !$nu))
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
            die ('admin_panel.class->close_form(): No form opened - stop.');
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
        if ($attrs && !is_array ($attrs))
            die ('admin_panel::open_table(): Argument is not an array.');

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
            die ('admin_panel::close_table(): Table stack underflow - stop.');
        if ($this->_opentable)
            return;

        if ($this->_openrow)
            die ('admin_panel::close_table(): ' . $this->_openrow . ' rows ' .
               'still open.');
        if ($this->_opencells)
            die ('admin_panel::close_table(): ' . $this->_opencells . ' cells ' .
               'still open.');

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
        if ($attrs && !is_array ($attrs))
            die ('admin_panel::open_row(): Argument is not an array.');

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
            die ('ADMIN_PANEL->close_row(): Table row stack underflow - stop.');
        if ($this->_openrow)
            return;
        if ($this->_opencells)
            die ('admin_panel::close_row(): ' . $this->_opencells . ' cells ' .
               'still open.');

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

        if ($attrs && !is_array ($attrs))
            die ('admin_panel::open_cell(): Argument is not an array.');

        if ($this->_opencells++)
            return;
        if (!$this->_openrow)
            die ('ADMIN_PANEL->open_cell(): No row for cell.');

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
            die ('ADMIN_PANEL->close_cell(): Table cell stack underflow - stop.');
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
        if (!is_array ($titles))
            die ('admin_panel::table_headers(): Need an array of field names.');
        if ($attrs && !is_array ($attrs))
            die ('admin_panel::table_headers(): Attributes are not in array.');

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
        if (!is_string ($field))
            die ('admin_panel::show(): Argument is not a string.');

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
        if (!is_string ($field))
            die ('admin_panel::show_ref(): Field name is not a string.');
        if (!is_string ($field))
            die ('admin_panel::show_ref(): Source name is not a string.');
        if (!is_string ($field))
            die ('admin_panel::show_ref(): Column name is not a string.');

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
        $c =& $this->v->cursor;

        if (!is_string ($field))
            die ('admin_panel::open_widget(): Field name is not a string.');
        if ($attrs && !is_array ($attrs))
            die ('admin_panel::open_widget(): Attributes are not in array.');

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
        if (!is_string ($field))
            die ('admin_panel::show_mime_image(): Field name is not a string.');
        if (!is_string ($type))
            die ('admin_panel::show_mime_image(): MIME type is not a string.');

        $v =& $this->v->cursor;
        $source = $v->source ();
        $url = $this->fileurl ($source, $field, $v->key (), $type,
                             $this->value ($field));

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
        if (!is_string ($field))
            die ('admin_panel::inputline(): Field name is not a string.');
        if (!is_int ($maxlen))
            die ('admin_panel::inputline(): MIME type is not a string.');

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
        if (!is_string ($field))
            die ('admin_panel::password(): Field name is not a string.');
        if (!is_int ($maxlen))
            die ('admin_panel::password(): MIME type is not a string.');

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
    function radiobox ($field, $label_true, $label_false,
                       $value_true = 1, $value_false = 0)
    {
        if (!is_string ($field))
            die ('admin_panel::radiobox(): Field name is not a string.');

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
        if (!is_string ($field))
            die ('admin_panel::select_string(): Field name is not a string.');
        if (!is_array ($optionlist))
            die ('admin_panel::select_string(): Option list is not an array.');

        if ($use_stringkey) {
            foreach ($optionlist as $string)
                $options[$string] = $string;
        } else
            $options = $optionlist;

        $this->open_widget ($field);
        $this->widgets->select ($this->new_formfield ($field),
                                $this->value ($field),
                                $options);
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
        if (!is_string ($field))
            die ('admin_panel::select_id(): Field name is not a string.');
        if (!is_string ($source))
            die ('admin_panel::select_id(): Source name is not a string.');
        if (!is_string ($column))
            die ('admin_panel::select_id(): Column name is not a string.');

        $options[0] = '-';
        $res = $this->db->select ("$column,$id", $source, '', $where);
        while ($row = $res->get ())
            $options[$row[$id]] = $row[$column];

        $this->open_widget ($field);
        $this->widgets->select ($this->new_formfield ($field),
                                $this->value ($field), $options);
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
        if (!is_string ($field))
            die ('admin_panel::textarea(): Field name is not a string.');
        if (!is_int ($width))
            die ('admin_panel::textarea(): Width is not an integer.');
        if (!is_int ($height))
            die ('admin_panel::textarea(): Height is not an integer.');

        $val = htmlentities ($this->value ($field));

        $this->open_widget ($field);
        $this->widgets->textarea ($this->new_formfield ($field),
                                  $width, $height, $val);
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
        if (!is_string ($field))
            die ('admin_panel::open_widget(): Field name is not a string.');

        $w =& $this->widgets;

        # Store type and fieldnames.
        $f =& new _form_element;
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
        if (!is_string ($field))
            die ('admin_panel::checkbox(): Field name is not a string.');
        if ($event && !is_a ($event, 'event'))
            die ('admin_panel::checkbox(): Argument 2 is not an event.');

        $w =& $this->widgets;

        $this->open_widget ($field);

        # This is a workaround which forces a value of 0 if the checkbox is
        # not selected.
        $w->hidden ($this->new_formfield ($field, $event), 0);

        # Print checkbox.
        $w->checkbox ($this->new_formfield ($field, $event), 1,
                      $this->value ($field));

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
    function submit_button ($label, $view = 0)
    {
        $f = new _form_element;
        $f->is_submit = true;

        $this->open_widget ('', array ('ALIGN' => 'CENTER'));
        $this->widgets->submit ($this->_new_formtoken ($view, $f), $label);
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
        echo '<A NAME="a' . $this->_anchors . '">';
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
        if (!is_a ($event, 'event'))
            die ('admin_panel::link(): Argument 2 is not an event.');
        if ($fakename && !is_string ($fakename))
            die ('admin_panel::link(): Argument 3 is not a string.');

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
        if (!is_a ($event, 'event'))
            die ('admin_panel::image_link(): Argument 3 is not an event.');

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
            $event =& new event ($event);
        else if (!is_a ($event, 'event'))
            die ('admin_panel::url(): Argument is not an event.');

        # Create a link.
        if (!$event->arg ('_cursor'))
            $event->set_arg ('_cursor', $c); # Add context.
        $link =& $this->application->link ($event);

        # Add anchor if activated and the link points to this event handler.
        if ($this->_anchor)
	    $link .= '#a' . $this->_anchors;

        return $link;
    }

    /**
     * Create form name for a field in a particular record.
     *
     * @access public
     * @param string $field
     * @param object event $event
     *        Event to trigger if widget is a submit button.
     * @param object _form_element $f For internal use.
     */
    function new_formfield ($field, $event = 0, $f = 0)
    {
        $v =& $this->v;
        $c =& $v->cursor;

        if ($event && !is_a ($event, 'event'))
            die ('admin_panel::new_formfield(): Argument 2 is not an event.');

        $c->set_field ($field);
        if (!$f)
            $f = new _form_element;

        return $this->_new_formtoken ($event, $f);
    }

    /**
     * Add context info to event.
     *
     * @param object event $event
     */
    function _add_context (&$element)
    {
        $v =& $this->v;

        $fi =& $this->_form_index;
        $element->form_idx = $fi ? $fi : 0;

        $element->cursor = $v->cursor;
        $element->view->args['_cursor'] =& $v->cursor;
    }

    /**
     * Create new name for a form element.
     *
     * This function creates a name containing a token
     *
     * @param object event $view XXX &$view would crash.
     * @param object _form_element $element
     */
    function _new_formtoken ($view, $element)
    {
        $tv = $this->application->event ();

        $view->subsession = $tv->subsession;

        $element->view = $view;
        $df =& $this->v->defaultfunc;
        if (isset ($df))
	    $element->defaultfunc = $df;
        if ($this->_form_filter)
	    $element->use_filter = $this->_form_filter;
        $element->element_filter_write = $this->_element_filter_write;

        $this->_add_context ($element);
        return 'item[' . $this->application->_tokens->create ($element) . ']';
    }
}
?>

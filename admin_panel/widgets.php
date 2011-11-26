<?php
# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once 'libinfo.php';

/**
 * Standard HTML widget set.
 *
 * @access public
 * @package User interface
 */
class widget_set {
    /**
     * Use relative links if true.
     *
     * @access public
     * @var boolean
     */
    var $short_links = false;

    /**
     * Initialise the class.
     *
     * @access public
     */
    function widget_set ()
    {
        $this->color['headline'] = '#00AAFF';
        $this->color['cell'] = 'CCCCCC';
        $this->color['table_header'] = '#00AA00';
        $this->color['msgbox'] = 'black';
        $this->color['msgbox_text'] = '#00FF00';
        $this->color['bgcolor'] = 'white';
        $this->color['text'] = 'black';
        $this->color['link'] = 'blue';
        $this->color['alink'] = 'black';
        $this->color['vlink'] = 'blue';
        $this->color['title bgcolor'] = '#4444FF';
    }

    /**
     * Create HTML attribute string from array.
     *
     * @param array $array Atribute values keyed by their names.
     * @returns string
     */
    function _attrs ($array)
    {
        if (is_array ($array) == false)
            return '';
        $out = '';
        foreach ($array as $key => $val)
            $out .= " $key=\"$val\"";
        return $out;
    }

    /**
     * Print document header
     *
     * @access public
     * @param string $header Application title
     * @param string $comp Application version
     */
    function header ($header, $comp)
    {
        $tmp = ($GLOBALS['SERVER_PORT'] == 443) ? 's' : '';

        echo "<HTML><HEAD>\n<TITLE>$header</TITLE>\n".
              "<!--\n" .
              "  Caroshi " . CAROSHI_VERSION . "\n" .
              "      http://www.devcon.net/\n" .
              "      http://lilly.devconsult.de/~sven\n" .
              "-->\n".
              "</HEAD>\n" .
              '<BODY BGCOLOR="' . $this->color['bgcolor'] .
              '" TEXT="' . $this->color['text'] . '" LINK="' . $this->color['link'] .
              '" ALINK="' . $this->color['alink'] . '" ' .
              'VLINK="' . $this->color['vlink'] . '">';
        if ($this->short_links)
            echo '<BASE HREF="http' . $tmp . '://' . $GLOBALS['SERVER_NAME'] . $GLOBALS['SCRIPT_NAME'] . '/">';
        echo "<TABLE BORDER=0 WIDTH=\"100%\">\n".
             "<TR><TD BGCOLOR=\"" . $this->color['title bgcolor'] .
             "\" WIDTH=\"100%\"><FONT COLOR=WHITE>" .
             "<B>$header</B></FONT></TD>";
        if ($comp)
          echo "<TD BGCOLOR=\"" . $this->color['title bgcolor'] .
               "\" ALIGN=RIGHT><FONT COLOR=YELLOW><B>" .
               "$comp</B></FONT></TD>";
        echo "</TR></TABLE>\n\n";
    }

    /**
     * Finish HTML document.
     *
     * @access public
     */
    function close ()
    {
        echo "\n</BODY>\n</HTML>\n";
    }

    /**
     * Print document footer.
     *
     * @access public
     */
    function footer ()
    {
        echo "\n";
        if ($this->short_links)
            echo "</BASE>";
        echo "</BODY>\n</HTML>\n";
    }

    /**
     * Print text.
     *
     * @access public
     * @param string $text
     */
    function print_text ($text)
    {
        echo $text;
    }

    /**
     * Print descriptive label.
     *
     * @access public
     * @param string $text
     */
    function print_label ($text)
    {
        echo "<b>$text</b>";
    }

    /**
     * Print headline.
     *
     * @access public
     * @param string $txt
     */
    function headline ($txt)
    {
        echo '<TABLE BORDER=0 WIDTH="100%" CELLPADDING="2">' .
             '<TR><TD BGCOLOR="' . $this->color['headline'] .
             '" WIDTH="100%"><FONT COLOR="WHITE"><B>' .
             $txt . '</B></FONT></TD></TR></TABLE>';
    }

    /**
     * Print message box.
     *
     * @access public
     * @param string $msg Message.
     * @param string $color HTML color.
     */
    function msgbox ($msg, $color = 0)
    {
        echo "<TABLE BORDER=0 WIDTH=\"100%\" BGCOLOR='" .
             $this->color['msgbox']  . "'>\n" . '<TR><TD ALIGN=CENTER>' .
	     "<FONT COLOR=\"" . ($color != 0 ? $color : $this->color['msgbox_text']) . "\"><B>$msg</B></FONT></TD></TR>\n" .
	     "</TABLE>\n";
    }

    /**
     * Open form.
     *
     * @access public
     * @param string $action URL to post form to.
     */
    function open_form ($action)
    {
        echo '<FORM ENCTYPE="multipart/form-data" ACTION="' . $action . '" METHOD=POST>' . "\n";
    }

    /**
     * Close form.
     *
     * @access public
     */
    function close_form ()
    {
        echo "</FORM>\n";
    }

    /**
     * Print image.
     *
     * @access public
     * @param string $alt Label.
     * @param string $src Image URL.
     */
    function image ($alt, $src)
    {
        echo '<IMG SRC="' . $src .'" BORDER="0"' . ($alt ? ' ALT="' . $alt . '"' : '') . '>';
    }

    /**
     * Print image link.
     *
     * @access public
     * @param string $alt Label.
     * @param string $src Image URL.
     * @param string $url Link URL.
     */
    function image_link ($alt, $src, $url)
    { 
        echo '<A HREF="' . $url . '"><IMG SRC="' . $src .'" ALT="' . htmlentities ($alt) . '" BORDER="0"></A>';
    }

    /**
     * Print input/password line.
     *
     * @param string $type Type of input line (text or password).
     * @param string $name Form element name.
     * @param string $val Content.
     * @param string $extra Extra HTML attributes.
     */
    function _input ($type, $name, $val = '', $extra = '')
    {
        echo '<INPUT TYPE="' . $type . '"';
        if ($name)
            echo ' NAME="'. $name . '"';
        if (!(is_string ($val) && $val == ''))
            echo ' VALUE="' . $val . '"';
        if ($extra)
            echo $extra;
        echo '>';
    }

    /**
     * Print input line.
     *
     * @access public
     * @param string $name Form element name.
     * @param string $val Content.
     * @param integer $size Width of input line in characters.
     */
    function inputline ($name, $val, $size)
    {
        $this->_input ('TEXT', $name, $val, ' SIZE="' . $size . '"');
    }

    /**
     * Print password line.
     *
     * @access public
     * @param string $name Form element name.
     * @param string $val Content.
     * @param integer $size Width of input line in characters.
     */
    function password ($name, $val, $size)
    {
        $this->_input ('PASSWORD', $name, $val, ' SIZE="' . $size . '"');
    }

    /**
     * Select key from string array.
     *
     * @access public
     * @param string $name Form element name.
     * @param string $current_val Initial string's key.
     * @param array $options
     */
    function select ($name, $current_val, $options)
    {
        echo "<SELECT NAME=\"". $name ."\">\n";
        foreach ($options as $val => $string) {
            echo '<OPTION VALUE="' . $val . '"';
            if ($current_val == $val)
                echo ' SELECTED';
            echo '>' . $string . "\n";
        }
        echo "</SELECT>\n";
    }

    /**
     * Print textarea
     *
     * @access public
     * @param string $name Form element name.
     * @param integer $cols Number of columns.
     * @param integer $rows Number of rows.
     * @param integer $val Content.
     */
    function textarea ($name, $cols, $rows, $val)
    {
        echo '<TEXTAREA NAME="' . $name . '" ' . 'ROWS="' . $rows . '" COLS="' . $cols . '" WRAP="VIRTUAL">' .
                 $val .
             '</TEXTAREA>';
    }

    /**
     * Create hidden field.
     *
     * @access public
     * @param string $name Form element name.
     * @param integer $val Content.
     */
    function hidden ($name, $val)
    {
        $this->_input ('HIDDEN', $name, $val);
    }

    /**
     * Create file upload form.
     *
     * @access public
     * @param string $name Form element name.
     */
    function fileform ($name)
    {
        $this->_input ('FILE', $name);
    }

    /**
     * Print radio box.
     *
     * @access public
     * @param string $name Form element name.
     * @param integer $val Value if box is set.
     * @param integer $current_val Current value.
     */
    function radiobox ($name, $val, $current_val)
    {
        $checked = '';
        if ($val == $current_val)
            $checked = ' CHECKED';
        $this->_input ('RADIO', $name, $val, $checked);
    }

    /**
     * Print checkbox.
     *
     * @access public
     * @param string $name Form element name.
     * @param integer $val Value if box is set.
     * @param integer $current_val Current value.
     */
    function checkbox ($name, $val, $current_val)
    {
        $checked = '';
        if ($val == $current_val)
            $checked = ' CHECKED';
        $this->_input ('CHECKBOX', $name, $val, $checked);
    }

    /**
     * Print submit button.
     *
     * @access public
     * @param string $name Form element name.
     * @param integer $val Value if box is set.
     */
    function submit ($name, $val)
    {
        $this->_input ('SUBMIT', $name, $val);
    }

    /**
     * Print submit image.
     *
     * @access public
     * @param string $label Label for image.
     * @param string $image_url URL to image.
     * @param string $name Form element name.
     */
    function submit_image ($label, $image_url, $name)
    {
        echo "<INPUT TYPE=\"IMAGE\" SRC=\"$image_url\" VALUE=\"$label\" " . "NAME=\"$name\" BORDER=\"0\">";
    }

    /**
     * Print form reset button.
     *
     * @access public
     * @param string $label Label.
     */
    function reset ($label)
    {
        $this->_input ('RESET', '', $label);
    }

    /**
     * Open a table.
     *
     * @access public
     * @param array $attrs Attribute values keyed by name.
     */
    function open_table ($attrs = '')
    {
        if (!$attrs)
            $attrs = array ();
        if (!isset ($attrs['WIDTH']))
            $attrs['WIDTH'] = '100%';
        if (!isset ($attrs['BORDER']))
            $attrs['BORDER'] = '0';
        if (!isset ($attrs['CELLPADDING']))
            $attrs['CELLPADDING'] = '1';
        if (!isset ($attrs['CELLSPACING']))
            $attrs['CELLSPACING'] = '1';

        echo '<TABLE' . $this->_attrs ($attrs) . '>';
    }

    /**
     * Close a table.
     *
     * @access public
     */
    function close_table ()
    {
        echo "</TABLE>\n";
    }

    /**
     * Open a row.
     *
     * @access public
     * @param array $attrs Attribute values keyed by name.
     */
    function open_row ($attrs = '')
    {
        echo '<TR' . $this->_attrs ($attrs) . '>';
    }

    /**
     * Close a row.
     *
     * @access public
     */
    function close_row ()
    {
        echo "</TR>\n";
    }

    /**
     * Open a cell.
     *
     * @access public
     * @param array $attrs Attribute values keyed by name.
     */
    function open_cell ($attrs = '')
    {
        echo '<TD' . $this->_attrs ($attrs) . '>';
    }

    /**
     * Close a cell.
     *
     * @access public
     */
    function close_cell ()
    {
        echo '</TD>';
    }

    /**
     * Create table header from array of strings.
     *
     * @access public
     * @param array $array Column titles.
     * @param array $attrs Attribute values keyed by name.
     */
    function table_headers ($array, $attrs = '')
    {
        if (!$attrs)
            $attrs = array ();
        if (!isset ($attrs['BGCOLOR']))
            $attrs['BGCOLOR'] = $this->color['table_header'];

        $this->open_row ();
        foreach ($array as $v)
	    echo '<TH' . $this->_attrs ($attrs) .
	         '><FONT COLOR="WHITE">' . $v . '</FONT></TH>';
        $this->close_row ();
    }
}
?>

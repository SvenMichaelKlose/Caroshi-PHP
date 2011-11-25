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

  # $Id: autoform.php,v 1.32 2002/06/25 01:03:24 sven Exp $
  #
  #
  # Copyright (c) 2001-2002 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
  # This library is free software; you can redistribute it and/or
  # modify it under the terms of the GNU Lesser General Public
  # License as published by the Free Software Foundation; either
  # version 2.1 of the License, or (at your option) any later version.
  #
  # This library is distributed in the hope that it will be useful,
  # but WITHOUT ANY WARRANTY; without even the implied warranty of
  # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
  # Lesser General Public License for more details.
  #
  # You should have received a copy of the GNU Lesser General Public
  # License along with this library; if not, write to the Free Software
  # Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

  # Widget generation flags.
  define ('TK_AUTOFORM_LABELS',   1); # Powers of 2.
  define ('TK_AUTOFORM_NO_INPUT', 2);

  /**
   * Initialise toolkit. Call this in your init() function.
   *
   * @access public
   * @param object application $this
   */
  function tk_autoform_init (&$this)
  {
  }

  /**
   * Create a widget from dbdepend description.
   *
   * @access public
   * @param object application $this
   * @param string $source Source name.
   * @param string $field Field name.
   * @param string $flags TK_AUTOFORM_LABELS or TK_AUTOFORM_NO_INPUT.
   */
  function tk_autoform_create_widget (&$this, $source, $field, $flags = 0)
  {
    $p =& admin_panel::instance ();
    $def =& $this->db->def;

    $type = $def->types ($source);
    if (!$type)
      die ("tk_autoform_create_widget(): No dbdepend definition for table " .
           "'$source.'");
    $type = $type[$field];

    # Don't print hidden fields.
    if (isset ($type['tk_autoform']['hide']))
      return;

    $p->open_row ();
    if ($flags & TK_AUTOFORM_LABELS)
      $p->label (isset ($type['d']) ? $type['d'] : $field);
    _tk_autoform_create_widget ($this, $type, $field, $flags);
    $p->close_row ();
  }

  function _tk_autoform_create_widget (&$this, &$type, $field, $flags)
  {
    $p =& admin_panel::instance ();
    $def =& $this->db->def;

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
   * @param object application $this
   * @param string $source Source name.
   */
  function tk_autoform_create_form (&$this, $source)
  {
    $def =& $this->db->def;
    $defs =& $def->types ($source);

    foreach ($defs as $field => $dummy)
      tk_autoform_create_widget ($this, $source, $field, TK_AUTOFORM_LABELS);
  }

  /**
   * List a result set initiated by get() before.
   *
   * The configuration array contains arrays keyed by source name, that can
   * contain the entries 'argname', 'view', 'call', 'fields' and 'selection'.
   * 'fields' can contain an array of record field names that should be
   * displayed. 'view' can contain the name of the event handler for links.
   * 'argname' takes the event argument name that takes the primary key of the
   * linked record (deprecated).
   *
   * @access public
   * @param object application $this
   * @param object cursor $c
   * @param array $config Configuration
   */
  function tk_autoform_list_cursor (&$this, &$c, $config)
  {
    $p =& admin_panel::instance ();

debug_dump ($c);
    if (!is_a ($c, 'cursor'))
      die ('tk_autoform_list_cursor(): Argument 2 is not a cursor.');
    if (!is_array ($config))
      die ('tk_autoform_list_cursor(): Argument 3 is not an array.');

    if (isset ($config['head_fields']))
      $p->table_headers ($config['head_fields']);

    $p->open_context ($c);

    $num = 0;
    while ($rec =& $c->get ()) {
      $source = $c->source ();
      $co = $config[$source];
      $pri = $this->db->def->primary ($source);
      $tt = $this->db->def->types ($source);
      $num++;

      @$argname = $co['argname'];
      @$view = $co['view'];
      @$call = $co['call'];
      @$fields = $co['fields'];
      if (!$fields) {
        if (!$tt)
          die ("No field list or dbdepend for source '$source'.");
        foreach ($tt as $n => $dummy)
          if ($n != $pri)
            $fields[] = $n;
      }
      if (!is_array ($fields))
        die ("Field list is not an array for source '$source'.");

      $p->open_row ();
      foreach ($fields as $name) {
        $p->open_cell (array ('ALIGN' => 'LEFT'));
        if (isset ($co['cell_functions'][$name])) {
          $co['cell_functions'][$name] (&$this);
        } else {
          if (isset ($tt[$name]['tk_autoform']['lookup'])) {
            $l =& $tt[$name]['tk_autoform']['lookup'];
            $table = $l['table'];
            $field = $l['field'];
            $p->show_ref ($name, $table, $field, $pri);
          } else {
 	    if (!($data = $rec[$name]))
	      $data = '-';
	    if ($data) {
              $data = ereg_replace (' ', '&nbsp;', $data);
              $arg = array ($argname => $rec[$pri]);
              $v =& new event ($view, $arg);
              if ($call)
                $v->set_caller ($this->event ());
              $p->link ($data, $v);
	    } else
	      $p->label ('&nbsp;');
          }
        }
        $p->close_cell ();
      }
      $p->close_row ();
    }
    $p->close_context ();

    $p->paragraph ();
    $p->print_text ("<b>Total: $num</b>");
  }
?>

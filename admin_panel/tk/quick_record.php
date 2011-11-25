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

  # $Id: quick_record.php,v 1.9 2002/06/01 01:33:18 sven Exp $$
  #
  # Copyright (c) 2001 dev/consulting GmbH
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

  # Initialise the module.
  function tk_quick_record_init (&$this)
  {
    $this->add_function ('tk_quick_record');
  }

  # $this->args:
  #	'source':    Name of source to edit
  #	'selection': Parameter to get() for list.
  #	'fields':    Fields to edit. (At the time an inputline).
  function tk_quick_record (&$this)
  {
    $args =& $this->args;
    $subargs =& $this->subargs;
    $ui =& admin_panel::instance ();
    $source = $this->arg ('source', ARG_SUB);
    $selection = $this->arg ('selection', ARG_SUB);

    $ui->headline ('Missing title');
    $ui->link ('zur&uuml;ck', 'return2caller');

    $ui->open_source ($source);
    if ($ui->query ()) {
      $ui->table_headers (array ('-', 'Missing header'));
      while ($p->get ()) {
        $ui->open_row ();

        $v =& new event ('record_delete');
        $v->set_next ($this->event);
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
    $v->set_next ($this->event);
    $ui->link ('Missing create label.', $v);
    $v =& new event ('form_update');
    $v->set_next ($this->event);
    $ui->submit_button ('Ok', $v);

    $ui->close_row ();
    $ui->close_source ();
  }
?>

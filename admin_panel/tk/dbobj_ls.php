<?php
  /**
   * Lister for dbobj.class' directory service.
   *
   * @access public
   * @module tk_dbobj_ls
   * @package User interface toolkits
   */

  # $Id: dbobj_ls.php,v 1.14 2002/06/01 01:33:18 sven Exp $
  #
  # Copyright (c) 2000-2001 dev/consulting GmbH
  #                         Sven Michael Klose <sven@devcon.net>
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

  /**
   * Initialise the toolkit.
   *
   * @access public
   * @param object application $this
   */
  function tk_dbobj_ls_init (&$this)
  {
    $this->add_function ('tk_dbobj_ls');
  }

  # Create a link if $table/$row[$this->db->primaries[$table]] is not the
  # cursor position.
  function _tk_dbobj_ls_node (&$this, $table, $row, $arg)
  {
    global $lang;

    $p =& admin_panel::instance ();

    $name = '<I>' . ereg_replace (' +', '&nbsp;', $row['name']) . '</I>';
    $id = $row['id'];
    $out = '';

    $out .= '/';

    $args['id'] = $id;

    # If this is the current position, only show where we are.
    if (!$this->arg ('dbobj_ls_link_current')
	&& $this->arg ('dbobj_ls_table') == $table
        && $this->arg ('dbobj_ls_id') == $id)
      $out .= '<B>' . $name . '</B>';
    else
      $out .= $p->_looselink ($name, new event ($this->event->name, $args));

    return $out;
  }

  /**
   * Event handler or widget: Show path and subdirectories.
   *
   * We should use admin_panel::open_widget here.
   *
   * @access public
   * @param object application $this
   * @param string $table Table name.
   * @param string $id Primary key of root node.
   */
  function tk_dbobj_ls (&$this, $table, $id, $link_current = false)
  {
    global $lang;

    $p =& admin_panel::instance ();

    # Create link path.
    $this->event->args['dbobj_ls_table'] = $table;
    $this->event->args['dbobj_ls_id'] = $id;
    $this->event->args['dbobj_ls_link_current'] = $link_current;
    echo $this->db->traverse_refs_from (
      $this, $table, $id, '_tk_dbobj_ls_node', 0, false
    );

    # List subcategories
    $res = $this->db->select (
      'name, id', $table, 'id_parent=' . $id, ' ORDER BY name ASC'
    );
    if ($res && $res->num_rows () > 0) {
      echo '<P>' . "\n" .
	   '<FONT COLOR="#888888"><B>' .
	   $lang['subdirectories'] .
	   ':</B></FONT>';
      while (list ($name, $id) = $res->get ()) {
        $p->link ($name, $this->event->name, array ('id' => $id));
        echo ' ';
      }
    }
    echo '<BR>';
  }
?>

<?php
  /**
   * Editor for moving single records in a SQL list.
   *
   * This should work with containers.
   *
   * @access public
   * @module tk_list_move
   * @package User interface toolkits
   */

  # $Id: list_move.php,v 1.17 2002/06/13 16:37:17 sven Exp $
  #
  # Copyright (c) 2001-2002 dev/consulting GmbH
  #                         Sven Michael Klose (sven@devcon.net)
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
  function tk_list_move_init (&$this)
  {
    $h = array ('tk_list_move', 'tk_list_move_to');
    util_add_functions ($this, $h);

    $h = array ( '_tk_list_move_go');
    util_add_raw_functions ($this, $h);
  }

  /**
   * Event handler: Toolkit entry point.
   *
   * Event arguments 'source', 'txt_choose', 'txt_choose_which',
   * 'txt_choose_dest', 'txt_moved', 'txt_no_record', 'func_record', 'selection'
   *
   * @access public
   * @param object application $this
   */
  function tk_list_move (&$this)
  {
    $txt = $this->arg ('txt_choose_which', ARG_SUB);

    _tk_list_move_list ($this, 'tk_list_move_to', $txt, false);
  }

  function tk_list_move_to (&$this)
  {
    $id_from = $this->arg ('id_from');
    $txt = $this->arg ('txt_choose_dest', ARG_SUB);
    $source = $this->arg ('source', ARG_SUB);

    $p =& admin_panel::instance ();

    $p->highlight[$p->view_id ($source, $id_from)] = 'yellow';
    _tk_list_move_list ($this, '_tk_list_move_go', $txt, true);
  }

  function _tk_list_move_go (&$this)
  {
    $id_from = $this->arg ('id_from');
    $id_to = $this->arg ('id_to');
    $id_parent = $this->arg ('id_parent', ARG_OPTIONAL);
    $source = $this->arg ('source', ARG_SUB);

    $p =& admin_panel::instance ();

    $ret = $this->db->move ($source, $id_from, $id_to, $id_parent);

    if ($ret)
      $p->msgbox ($this->arg ('txt_not_moved', ARG_SUB), 'red');
    else
      $p->msgbox ($this->arg ('txt_moved', ARG_SUB));

    $p->highlight[$p->view_id ($source, $id_from)] = '#00FF00';
    $this->call ('return2caller');
  }

  function _tk_list_move_destlink (&$this)
  {
    $id_from = $this->arg ('id_from');
    $source = $this->arg ('source', ARG_SUB);
    $txt = $this->arg ('txt_choose', ARG_SUB);

    $p =& admin_panel::instance ();

    if ($id_from == $p->v->key)
      return;

    $p->paragraph ();
    $p->open_row ();
    $p->open_cell (array ('ALIGN' => 'CENTER'));
    $arg = array ('id_from' => $id_from, 'id_to' => $p->v->key);
    if ($c_parent = $this->db->def->ref_id ($source))
      $arg['id_parent'] = $this->db->column ($source, $c_parent, $id_from);
    $p->link ($txt, new event ('_tk_list_move_go', $arg));
    $p->close_cell ();
    $p->close_row ();
  }

  function _tk_list_move_list (&$this, $func_link, $msg, $mode)
  {
    # Check arguments.
    $this->arg ('txt_choose_which', ARG_SUB);
    $this->arg ('txt_choose_dest', ARG_SUB);
    $this->arg ('txt_choose', ARG_SUB);
    $this->arg ('txt_not_moved', ARG_SUB);
    $this->arg ('txt_moved', ARG_SUB);
    $this->arg ('txt_no_record', ARG_SUB);

    $source = $this->arg ('source', ARG_SUB);
    $selection = $this->arg ('selection', ARG_SUB);
    $func_record = $this->arg ('func_record', ARG_SUB);

    $p =& admin_panel::instance ();

    $p->open_source ($source);
    if ($p->query ($selection, true)) {
      $p->msgbox ($msg);
      while ($p->get ()) {
	if ($mode)
	  _tk_list_move_destlink ($this);
        $p->paragraph ();
        $p->open_row ();
        $func_record ($this);
	if (!$mode) {
          $v =& new event (func_link, array ('id_from' => $v->key));
	  $p->link ('choose', $v);
        }
        $p->close_row ();
        $p->paragraph ();
      }
      $c =& $p->get_cursor ();
      $c->use_key (0);
      if ($mode)
        _tk_list_move_destlink ($this);
    } else {
      $p->msgbox ($this->arg ('txt_no_record'));
      $this->call ('return2caller');
      return;
    }
    $p->close_source ();
  }
?>

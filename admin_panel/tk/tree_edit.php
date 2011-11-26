<?php
/**
 * Tree editor for admin_panel.class.
 *
 * @access public
 * @module tk_tree_edit
 * @package User interface toolkits
 */

# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

# Initialise the module.
# XXX This should be tree_edit_init().
function tree_edit_register (&$this)
  {
    $this->add_function ('tree_edit_move');
    $this->add_function ('move_node_to');
    $this->add_function ('move_node4real');
    $this->raw_views['move_node4real'] = true;
}

function tree_edit (&$this, &$args)
{
    $ui =& admin_panel::instance ();
    $te =& $this->event ();

    foreach ($args as $name => $data)
        $te->set_arg ($name, $data);
    $table = $this->arg ('source');
    $id = $this->arg ('id');
    $nodefunc = $this->arg ('nodefunc', ARG_OPTIONAL);
    $def =& $this->db->def;

    # Display category tree.
    echo "<CENTER><TABLE BORDER=0 BGCOLOR=\"#EEEEEE\"><TR><TD>";
    $tree =& new DBTREE ($this->db, $table, $id);
    if (isset ($ui->highlight))
        $tree->highlight = $ui->highlight;
    $tree->view ($nodefunc ? $nodefunc : 'tv_node', $this);
    echo '</TD></TR></TABLE></CENTER>';
}

# Generic view of a node.
# $this->args:
#   'name'		Name of name field.
#   'id'		Name of primary key.
#   'nodeview'	View for contents of a node.
#   'nodecreator'	View to create a node.
#   'no_create'	Inhibit link to create a new subnode
function tv_node (&$this, &$node)
{
    $p =& admin_panel::instance ();
    $nid = $this->arg ('id');

    $name =& $node[$this->arg ('name')];
    $nodeview =& $this->arg ('nodeview');
    $no_create =& $this->arg ('no_create', ARG_OPTIONAL);

    if (!$name)
        $name = $this->arg ('txt_unnamed');
    $id = $node[$nid];
    $str = '<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR>' .
           '<TD WIDTH="100%" ALIGN="LEFT">' .
    	        $p->link ("<B>$name</B> ", new event($nodeview, array ('id' => $id))) .
	   '</TD>';
    if (!$no_create)
        $str .= '<TD><font size="-1">' .
	        $p->link ('+&gt;', new event ($this->arg ('nodecreator'), array ('id' => $id, '__next' => $this->args ()))) .
                '</font></td>';
    $str .= '</TR></TABLE>';
}

# $this->args:
#   'name'		Name of name field.
#   'id'		Name of primary key.
function tv_move_node (&$this, &$node)
{
    $p =& admin_panel::instance ();

    $name = $node[$this->arg ('name')];
    $id = $node[$this->arg ('id')];
    $p->link ("<B>$name</B>", new event ('move_node_to', array_merge ($this->event->args, array ('id_src' => $id))));
}

# $this->args:
#   'name'		Name of name field.
#   'id'		Name of primary key.
#   'id_src'		Primary key of node to move.
function tv_move_to_node (&$this, &$node)
{
    $p =& admin_panel::instance ();

    $name = $node[$this->arg ('name')];
    $id = $node[$this->arg ('id')];
    $id_src = $this->arg ('id_src');
    $table = $this->arg ('source');

    $id_parent = $node[$this->db->def->ref_id ($table)];

    if ($id == $id_src)
        return "<B>$name</B>";
    return "<b>$name</b> " . $p->link ('^', new event ('move_node4real', array_merge ($this->args, array ('id_dest' => $id_parent,
                                                                                                          'id_src' => $id_src, 'id_dest_next' => $id)))) .
           ' ' . $p->link ('\/', new event ('move_node4real', array_merge ($this->args, array ('id_dest' => $id_parent,
                                                                                                'id_src' => $id_src,
      	                                                                                        'id_dest_next' => $node['id_next'])))) .
           ' ' . $p->link ('>', new event ('move_node4real', array_merge ($this->args, array ('id_dest' => $id,
                                                                                              'id_src' => $id_src))));
}

# $this->args:
#   'source'		Name of table containing the nodes.
function move_node_to (&$this)
{
    $def =& $this->db->def;
    $ui =& admin_panel::instance ();

    $table = $this->arg ('source');
    $id = $this->arg ('id');
    $id_src = $this->arg ('id_src');
    $txt_back = $this->arg ('txt_back');
    $txt_select_dest = $this->arg ('txt_select_dest');

    $p->msgbox ("$txt_select_dest:", 'yellow');
    $p->link ($txt_back, 'return2caller');
    echo "<CENTER><TABLE BORDER=0 BGCOLOR=\"#EEEEEE\"><TR><TD>";
    $tree =& new DBTREE ($this->db, $table, $id);
    $tree->highlight[$p->view_id ($table, $id_src)] = 'yellow';
    $tree->view ('tv_move_to_node', $this);
    echo '</TD></TR></TABLE></CENTER>';
}

# $this->args:
#   'source'		Name of table containing the nodes.
#   'name'		Name of name field.
#   'id_src'		Primary key of node to move.
#   'id_dest'		Primary key of destination node.
#   'id_dest_next'	Primary key of destination/next sibling.
#   'id'		Name of primary key.
function move_node4real (&$this)
{
    $def =& $this->db->def;
    $ui =& admin_panel::instance ();

    $table = $this->arg ('source');
    $id = $this->arg ('id');
    $id_src = $this->arg ('id_src');
    $id_dest = $this->arg ('id_dest');
    $id_dest_next = $this->arg ('id_dest_next', ARG_OPTIONAL);
    $txt_moved = $this->arg ('txt_moved');
    $txt_not_moved = $this->arg ('txt_not_moved');
    $txt_move_again = $this->arg ('txt_move_again');

    if (!$this->db->move ($table, $id_src, $id_dest_next, $id_dest))
        $ui->msgbox ($txt_moved);
    else
        $ui->msgbox ($txt_not_moved, 'red');

    $ui->link ($txt_move_again, new event ('move_node_to', array_merge ($this->args, array ('id_src' => $id_src))));

    $ui->highlight[$ui->view_id ($table, $id_src)] = '#00FF00';

    $this->call ('return2caller');
}

# $this->args:
#   'source'		Name of table containing the nodes.
#   'name'		Name of name field.
#   'id'		Name of primary key.
function tree_edit_move (&$this)
{
    $p =& admin_panel::instance ();

    $table = $this->arg ('source');
    $id = $this->arg ('id');
    $txt_back = $this->arg ('txt_back');
    $txt_select_node = $this->arg ('txt_select_node');
    $def =& $this->db->def;

    $p->msgbox ("$txt_select_node:", 'yellow');
    $p->link ($txt_back, 'return2caller');
    echo "<CENTER><TABLE BORDER=0 BGCOLOR=\"#EEEEEE\"><TR><TD>";

    $tree =& new DBTREE ($this->db, $table, $id);
    $tree->view ('tv_move_node', $this);

    echo '</TD></TR></TABLE></CENTER>';
}
?>

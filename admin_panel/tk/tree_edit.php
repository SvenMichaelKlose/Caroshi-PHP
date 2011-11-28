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
function tree_edit_register (&$app)
  {
    $app->add_function ('tree_edit_move');
    $app->add_function ('move_node_to');
    $app->add_function ('move_node4real');
    $app->raw_views['move_node4real'] = true;
}

function tree_edit (&$app, &$args)
{
    $ui =& $app->ui;
    $te = $app->event ();

    foreach ($args as $name => $data)
        $te->set_arg ($name, $data);
    $table = $app->arg ('source');
    $id = $app->arg ('id');
    $nodefunc = $app->arg ('nodefunc', ARG_OPTIONAL);
    $def =& $app->db->def;

    # Display category tree.
    echo "<CENTER><TABLE BORDER=0 BGCOLOR=\"#EEEEEE\"><TR><TD>";
    $tree = new DBTREE ($app->db, $table, $id);
    if (isset ($ui->highlight))
        $tree->highlight = $ui->highlight;
    $tree->view ($nodefunc ? $nodefunc : 'tv_node', $app);
    echo '</TD></TR></TABLE></CENTER>';
}

# Generic view of a node.
# $app->args:
#   'name'		Name of name field.
#   'id'		Name of primary key.
#   'nodeview'	View for contents of a node.
#   'nodecreator'	View to create a node.
#   'no_create'	Inhibit link to create a new subnode
function tv_node (&$app, &$node)
{
    $p =& $app->ui;
    $nid = $app->arg ('id');

    $name = $node[$app->arg ('name')];
    $nodeview = $app->arg ('nodeview');
    $no_create = $app->arg ('no_create', ARG_OPTIONAL);

    if (!$name)
        $name = $app->arg ('txt_unnamed');
    $id = $node[$nid];
    $str = '<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR>' .
           '<TD WIDTH="100%" ALIGN="LEFT">' .
    	        $p->link ("<B>$name</B> ", new event($nodeview, array ('id' => $id))) .
	   '</TD>';
    if (!$no_create)
        $str .= '<TD><font size="-1">' .
	        $p->link ('+&gt;', new event ($app->arg ('nodecreator'), array ('id' => $id, '__next' => $app->args ()))) .
                '</font></td>';
    $str .= '</TR></TABLE>';
}

# $app->args:
#   'name'		Name of name field.
#   'id'		Name of primary key.
function tv_move_node (&$app, &$node)
{
    $p =& $app->ui;

    $name = $node[$app->arg ('name')];
    $id = $node[$app->arg ('id')];
    $p->link ("<B>$name</B>", new event ('move_node_to', array_merge ($app->event ()->args, array ('id_src' => $id))));
}

# $app->args:
#   'name'		Name of name field.
#   'id'		Name of primary key.
#   'id_src'		Primary key of node to move.
function tv_move_to_node (&$app, &$node)
{
    $p =& $app->ui;

    $name = $node[$app->arg ('name')];
    $id = $node[$app->arg ('id')];
    $id_src = $app->arg ('id_src');
    $table = $app->arg ('source');

    $id_parent = $node[$app->db->def->ref_id ($table)];

    if ($id == $id_src)
        return "<B>$name</B>";
    return "<b>$name</b> " . $p->link ('^', new event ('move_node4real', array_merge ($app->args (), array ('id_dest' => $id_parent,
                                                                                                            'id_src' => $id_src, 'id_dest_next' => $id)))) .
           ' ' . $p->link ('\/', new event ('move_node4real', array_merge ($app->args (), array ('id_dest' => $id_parent,
                                                                                                 'id_src' => $id_src,
      	                                                                                         'id_dest_next' => $node['id_next'])))) .
           ' ' . $p->link ('>', new event ('move_node4real', array_merge ($app->args (), array ('id_dest' => $id,
                                                                                                'id_src' => $id_src))));
}

# $app->args:
#   'source'		Name of table containing the nodes.
function move_node_to (&$app)
{
    $def =& $app->db->def;
    $p =& $app->ui;

    $table = $app->arg ('source');
    $id = $app->arg ('id');
    $id_src = $app->arg ('id_src');
    $txt_back = $app->arg ('txt_back');
    $txt_select_dest = $app->arg ('txt_select_dest');

    $p->msgbox ("$txt_select_dest:", 'yellow');
    $p->link ($txt_back, 'return2caller');
    echo "<CENTER><TABLE BORDER=0 BGCOLOR=\"#EEEEEE\"><TR><TD>";
    $tree = new DBTREE ($app->db, $table, $id);
    $c = new cursor_sql ();
    $c->set_source ($table);
    $c->set_key ($id_src);
    $tree->highlight[$c->id ()] = 'yellow';
    $tree->view ('tv_move_to_node', $app);
    echo '</TD></TR></TABLE></CENTER>';
}

# $app->args:
#   'source'		Name of table containing the nodes.
#   'name'		Name of name field.
#   'id_src'		Primary key of node to move.
#   'id_dest'		Primary key of destination node.
#   'id_dest_next'	Primary key of destination/next sibling.
#   'id'		Name of primary key.
function move_node4real (&$app)
{
    $def =& $app->db->def;
    $ui =& $app->ui;

    $table = $app->arg ('source');
    $id = $app->arg ('id');
    $id_src = $app->arg ('id_src');
    $id_dest = $app->arg ('id_dest');
    $id_dest_next = $app->arg ('id_dest_next', ARG_OPTIONAL);
    $txt_moved = $app->arg ('txt_moved');
    $txt_not_moved = $app->arg ('txt_not_moved');
    $txt_move_again = $app->arg ('txt_move_again');

    if (!$app->db->move ($table, $id_src, $id_dest_next, $id_dest))
        $ui->msgbox ($txt_moved);
    else
        $ui->msgbox ($txt_not_moved, 'red');

    $ui->link ($txt_move_again, new event ('move_node_to', array_merge ($app->args, array ('id_src' => $id_src))));

    $ui->highlight[$ui->view_id ($table, $id_src)] = '#00FF00';

    $app->call ('return2caller');
}

# $app->args:
#   'source'		Name of table containing the nodes.
#   'name'		Name of name field.
#   'id'		Name of primary key.
function tree_edit_move (&$app)
{
    $p =& $app->ui;

    $table = $app->arg ('source');
    $id = $app->arg ('id');
    $txt_back = $app->arg ('txt_back');
    $txt_select_node = $app->arg ('txt_select_node');
    $def =& $app->db->def;

    $p->msgbox ("$txt_select_node:", 'yellow');
    $p->link ($txt_back, 'return2caller');
    echo "<CENTER><TABLE BORDER=0 BGCOLOR=\"#EEEEEE\"><TR><TD>";

    $tree = new DBTREE ($app->db, $table, $id);
    $tree->view ('tv_move_node', $app);

    echo '</TD></TR></TABLE></CENTER>';
}
?>

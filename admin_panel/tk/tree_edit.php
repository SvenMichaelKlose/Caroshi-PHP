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

class tree_edit_conf {
    var $source;
    var $treeview;
    var $nodeview;
    var $nodecreator;
    var $rootname;
    var $table;
    var $name;
    var $id;
    var $preset_values = null;
    var $txt_select_node;
    var $txt_select_dest;
    var $txt_moved;
    var $txt_not_moved;
    var $txt_move_again;
    var $txt_back;
    var $txt_unnamed;
};

function tree_edit (&$app, &$conf)
{
    $ui =& $app->ui;
    $app->event ()->set_arg ('conf', $conf);

    # Display category tree.
    echo "<CENTER><TABLE BORDER=0 BGCOLOR=\"#EEEEEE\"><TR><TD>";
    $tree = new DBTREE ($app->db, $conf->table, $conf->id, $conf->preset_values);
    if (isset ($ui->highlight))
        $tree->highlight = $ui->highlight;
    $tree->view ('tv_node', $app);
    echo '</TD></TR></TABLE></CENTER>';
}

function tv_node (&$app, &$node)
{
    $p =& $app->ui;
    $conf = $app->arg ('conf');
    $name = $node[$conf->name];
    if (!$name)
        $name = $conf->txt_unnamed;
    $id = $node[$conf->id];

    $str = '<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR>' .
           '<TD WIDTH="100%" ALIGN="LEFT">' .
            $p->link ("<B>$name</B> ", new event($conf->nodeview, array ('id' => $id))) .
	   '</TD>';
    if (true) { #!$no_create) {
	$e = new event ($conf->nodecreator, array_merge ($conf->preset_values, array ('id_parent' => $id)));
        $e->set_next ($app->event ());
        $str .= '<TD><font size="-1">' . $p->link ('+&gt;', $e) . '</font></td>';
    }
    $str .= '</TR></TABLE>';
}

function tv_move_node (&$app, &$node)
{
    $conf = $app->arg ('conf');
    $name = $node[$conf->name];

    $app->ui->link ("<B>$name</B>", new event ('move_node_to', array_merge ($app->args (), array ('id_src' => $node[$conf->id]))));
}

function tv_move_to_node (&$app, &$node)
{
    $p =& $app->ui;
    $conf = $app->arg ('conf');
    $name = $node[$conf->name];
    $id = $node[$conf->id];
    $id_src = $app->arg ('id_src');
    $id_parent = $node[$app->db->def->id_parent ($conf->table)];

    if ($id == $id_src)
        return "<B>$name</B>";
    $args = array_merge ($app->args (), array ('id_dest' => $id_parent,
                                               'id_src' => $id_src));
    return "<b>$name</b> " . $p->link ('^', new event ('move_node4real', array_merge ($args, array ('id_dest_next' => $id)))) .
           ' ' . $p->link ('\/', new event ('move_node4real', array_merge ($args, array ('id_dest_next' => $node['id_next'])))) .
           ' ' . $p->link ('>', new event ('move_node4real', $args));
}

# $app->args:
#   'source'		Name of table containing the nodes.
function move_node_to (&$app)
{
    $def =& $app->db->def;
    $p =& $app->ui;
    $conf = $app->arg ('conf');
    $id_src = $app->arg ('id_src');

    $p->msgbox ("$conf->txt_select_dest:", 'yellow');
    $p->link ($conf->txt_back, 'return2caller');
    echo "<CENTER><TABLE BORDER=0 BGCOLOR=\"#EEEEEE\"><TR><TD>";
    $tree = new DBTREE ($app->db, $conf->table, $conf->id, $conf->preset_values);
    $c = new cursor_sql ();
    $c->set_source ($conf->table);
    $c->set_key ($id_src);
    $tree->highlight[$c->id ()] = 'yellow';
    $tree->view ('tv_move_to_node', $app);
    echo '</TD></TR></TABLE></CENTER>';
}

function move_node4real (&$app)
{
    $def =& $app->db->def;
    $ui =& $app->ui;
    $conf = $app->arg ('conf');
    $id_src = $app->arg ('id_src');
    $id_dest = $app->arg ('id_dest');
    $id_dest_next = $app->arg ('id_dest_next', ARG_OPTIONAL);

    if (!$app->db->move ($conf->table, $id_src, $id_dest_next, $id_dest))
        $ui->msgbox ($conf->txt_moved);
    else
        $ui->msgbox ($conf->txt_not_moved, 'red');

    $ui->link ($conf->txt_move_again, new event ('move_node_to', array_merge ($app->args (), array ('id_src' => $id_src))));
    $ui->highlight[$conf->table . $id_src] = '#00FF00';
    $app->call ('return2caller');
}

function tree_edit_move (&$app)
{
    $p =& $app->ui;
    $conf = $app->arg ('conf');

    $p->msgbox ("$conf->txt_select_node:", 'yellow');
    $p->link ($conf->txt_back, 'return2caller');

    echo "<CENTER><TABLE BORDER=0 BGCOLOR=\"#EEEEEE\"><TR><TD>";
    $tree = new DBTREE ($app->db, $conf->table, $conf->id, $conf->preset_values);
    $tree->view ('tv_move_node', $app);
    echo '</TD></TR></TABLE></CENTER>';
}

?>

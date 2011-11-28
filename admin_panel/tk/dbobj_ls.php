<?php

/**
 * Lister for dbobj.class' directory service.
 *
 * @access public
 * @module tk_dbobj_ls
 * @package User interface toolkits
 */

# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

/**
 * Initialise the toolkit.
 *
 * @access public
 * @param object application $app
 */
function tk_dbobj_ls_init (&$app)
{
    $app->add_function ('tk_dbobj_ls');
}

# Create a link if $table/$row[$app->db->primaries[$table]] is not the
# cursor position.
function _tk_dbobj_ls_node (&$app, $table, $row, $arg)
{
    global $lang;

    $p =& $app->ui;

    $name = '<I>' . ereg_replace (' +', '&nbsp;', $row['name']) . '</I>';
    $id = $row['id'];
    $out = '';

    $out .= '/';

    $args['id'] = $id;

    # If this is the current position, only show where we are.
    if (!$app->arg ('dbobj_ls_link_current')
	&& $app->arg ('dbobj_ls_table') == $table
        && $app->arg ('dbobj_ls_id') == $id)
        $out .= "<B>$name</B>";
    else
        $out .= $p->_looselink ($name, new event ($app->event->name, $args));

    return $out;
}

/**
 * Event handler or widget: Show path and subdirectories.
 *
 * We should use admin_panel::open_widget here.
 *
 * @access public
 * @param object application $app
 * @param string $table Table name.
 * @param string $id Primary key of root node.
 */
function tk_dbobj_ls (&$app, $table, $id, $link_current = false)
{
    global $lang;

    $p =& $app->ui;

    # Create link path.
    $app->event->args['dbobj_ls_table'] = $table;
    $app->event->args['dbobj_ls_id'] = $id;
    $app->event->args['dbobj_ls_link_current'] = $link_current;
    echo $app->db->traverse_refs_from ($app, $table, $id, '_tk_dbobj_ls_node', 0, false);

    # List subcategories
    if ($res = $app->db->select ('name, id', $table, "id_parent=$id ORDER BY name ASC")) {
        echo '<P>' . "\n" .  '<FONT COLOR="#888888"><B>' . $lang['subdirectories'] . ':</B></FONT>';
        while (list ($name, $id) = $res->get ()) {
            $p->link ($name, $app->event->name, array ('id' => $id));
            echo ' ';
        }
    }
    echo '<BR>';
}
?>

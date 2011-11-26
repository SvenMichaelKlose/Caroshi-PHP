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
        $out .= "<B>$name</B>";
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
    echo $this->db->traverse_refs_from ($this, $table, $id, '_tk_dbobj_ls_node', 0, false);

    # List subcategories
    if ($res = $this->db->select ('name, id', $table, "id_parent=$id ORDER BY name ASC")) {
        echo '<P>' . "\n" .  '<FONT COLOR="#888888"><B>' . $lang['subdirectories'] . ':</B></FONT>';
        while (list ($name, $id) = $res->get ()) {
            $p->link ($name, $this->event->name, array ('id' => $id));
            echo ' ';
        }
    }
    echo '<BR>';
}
?>

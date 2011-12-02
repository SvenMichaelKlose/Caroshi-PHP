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

# Copyright (c) 2001-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

/**
 * Initialise the toolkit.
 *
 * @access public
 * @param object application $app
 */
function tk_list_move_init (&$app)
{
    $h = array ('tk_list_move', 'tk_list_move_to');
    util_add_functions ($app, $h);

    $h = array ( '_tk_list_move_go');
    util_add_raw_functions ($app, $h);
}

/**
 * Event handler: Toolkit entry point.
 *
 * Event arguments 'source', 'txt_choose', 'txt_choose_which',
 * 'txt_choose_dest', 'txt_moved', 'txt_no_record', 'func_record', 'selection'
 *
 * @access public
 * @param object application $app
 */
function tk_list_move (&$app)
{
    $txt = $app->arg ('txt_choose_which', ARG_SUB);

    _tk_list_move_list ($app, 'tk_list_move_to', $txt, false);
}

function tk_list_move_to (&$app)
{
    $id_from = $app->arg ('id_from');
    $txt = $app->arg ('txt_choose_dest', ARG_SUB);
    $source = $app->arg ('source', ARG_SUB);

    $p =& $app->ui;

    $p->highlight[$p->view_id ($source, $id_from)] = 'yellow';
    _tk_list_move_list ($app, '_tk_list_move_go', $txt, true);
}

function _tk_list_move_go (&$app)
{
    $id_from = $app->arg ('id_from');
    $id_to = $app->arg ('id_to');
    $id_parent = $app->arg ('id_parent', ARG_OPTIONAL);
    $source = $app->arg ('source', ARG_SUB);

    $p =& $app->ui;

    $ret = $app->db->move ($source, $id_from, $id_to, $id_parent);

    if ($ret)
        $p->msgbox ($app->arg ('txt_not_moved', ARG_SUB), 'red');
    else
        $p->msgbox ($app->arg ('txt_moved', ARG_SUB));

    $p->highlight[$p->view_id ($source, $id_from)] = '#00FF00';
    $app->call ('return2caller');
}

function _tk_list_move_destlink (&$app)
{
    $id_from = $app->arg ('id_from');
    $source = $app->arg ('source', ARG_SUB);
    $txt = $app->arg ('txt_choose', ARG_SUB);

    $p =& $app->ui;

    if ($id_from == $p->v->key)
        return;

    $p->paragraph ();
    $p->open_row ();
    $p->open_cell (array ('ALIGN' => 'CENTER'));
    $arg = array ('id_from' => $id_from, 'id_to' => $p->v->key);
    if ($c_parent = $app->db->def->id_parent ($source))
        $arg['id_parent'] = $app->db->column ($source, $c_parent, $id_from);
    $p->link ($txt, new event ('_tk_list_move_go', $arg));
    $p->close_cell ();
    $p->close_row ();
}

function _tk_list_move_list (&$app, $func_link, $msg, $mode)
{
    # Check arguments.
    $app->arg ('txt_choose_which', ARG_SUB);
    $app->arg ('txt_choose_dest', ARG_SUB);
    $app->arg ('txt_choose', ARG_SUB);
    $app->arg ('txt_not_moved', ARG_SUB);
    $app->arg ('txt_moved', ARG_SUB);
    $app->arg ('txt_no_record', ARG_SUB);

    $source = $app->arg ('source', ARG_SUB);
    $selection = $app->arg ('selection', ARG_SUB);
    $func_record = $app->arg ('func_record', ARG_SUB);

    $p =& $app->ui;

    $p->open_source ($source);
    if ($p->query ($selection, true)) {
        $p->msgbox ($msg);
        while ($p->get ()) {
	    if ($mode)
	        _tk_list_move_destlink ($app);
            $p->paragraph ();
            $p->open_row ();
            $func_record ($app);
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
            _tk_list_move_destlink ($app);
    } else {
        $p->msgbox ($app->arg ('txt_no_record'));
        $app->call ('return2caller');
        return;
    }
    $p->close_source ();
}

?>

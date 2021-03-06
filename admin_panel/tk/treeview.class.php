<?php

# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

/**
 * Tree widget.
 *
 * @deprec
 * @access public
 * @module tk_dbtree
 * @package User interface toolkits
 */
class DBTREE {
    var $db;
    var $_nodes;
    var $_table;
    var $_c_id;
    var $_c_id_parent;
    var $_c_last;
    var $_c_next;
    var $highlight;
    var $_preset_values;

    # Initialize with basic tree info.
    # $db     = DBCtrl || DBI object.
    # $table  = Name of table that contains tree nodes.
    # $c_id   = Name of primary key.
    # $c_id_parent = Name of column referencing a parent node.
    function DBTREE (&$db, $table, $c_id, $preset_values)
    {
        $c_id_parent = $db->def->id_parent ($table);
        $c_last = $db->def->id_prev ($table);
        $c_next = $db->def->id_next ($table);

        # Read in tree nodes.
        $res = $db->select ('*', $table, sql_selection_assignments ($preset_values));
        while ($res && $r = $res->get ()) {
            if (!$c_id_parent || !$r[$c_id_parent])
                $r[$c_id_parent] = '0';
            if (!trim ($r['name']))
                $r['name'] = $GLOBALS['lang']['unnamed'];
            $this->_nodes[$r[$c_id]] = $r;
        }

        $this->db =& $db;
        $this->_table = $table;
        $this->_c_id = $c_id;
        $this->_c_id_parent = $c_id_parent;
        $this->_c_last = $c_last;
        $this->_c_next = $c_next;
        $this->_preset_values = $preset_values;
    }

    # Returns sorted primary keys of child nodes.
    # $id = Primary key of the parent node.
    function _children_of ($id)
    {
        $def =& $this->db->def;
        $table = $this->_table;

        if (!is_array ($this->_nodes))
	    return 0;
        $r = '';
        for ($val = reset ($this->_nodes); $val; $val = next ($this->_nodes))
            if ($val[$this->_c_id_parent] == $id)
                $r[] = $val[$this->_c_id];
        if (!is_array ($r) || !$this->_c_last)
            return $r;

        # Seek first node in list.
        for ($id = reset ($r); $id; $id = next ($r))
            if (!$this->_nodes[$id][$this->_c_last])
	        break;
        $o[] = $id;

        # Add followers.
        while ($id = $this->_nodes[$id][$this->_c_next])
	    $o[] = $id;

        return $o;
    }

    function print_children_of ($id, $indent, $view, &$app)
    {
        if (!$id)
            $id = '0';
        if (!$children = $this->_children_of ($id))
            return false;
        for ($id = reset ($children); $id; $id = next ($children)) {
            if (!isset ($newindent))
                $newindent = "$indent<td>&nbsp;</td>";
            else
	        echo "<tr>$newindent";
            echo '<td bgcolor="';
            if (isset ($this->highlight[$this->_table . $id]))
	        echo $this->highlight[$this->_table . $id];
	    else
	        echo '#cccccc';
            echo '">';
            echo $view ($app, $this->_nodes[$id]);
	    echo '</td>';
            if (!$this->print_children_of ($id, $newindent, $view, $app))
	        echo "</tr>\n";
        }
        return true;
    }

    # Print tree in a HTML table.
    function view ($view, &$app)
    {
        $db =& $app->db;
        $def =& $app->db->def;
        $table = $this->_table;

        if (!isset ($this->_nodes))
	    return;
        echo '<table border=0><tr><td bgcolor="#cccccc">';
        $pri = $def->primary ($table);
        $id_parent = $def->id_parent ($table);
        $root_id = $db->select ($pri, $table, sql_selection_assignments (array_merge ($this->_preset_values, array ($id_parent, '0'))))->get ($pri);
        echo $view ($app, $this->_nodes[$root_id]);
        echo '</td>';
        $this->print_children_of ($root_id, '', $view, $app);
        echo '</table>';
    }
}

?>

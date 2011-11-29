<?php
# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once PATH_TO_CAROSHI . '/dbi/sql.php';
require_once PATH_TO_CAROSHI . '/dbi/dbctrl.class.php';
require_once PATH_TO_CAROSHI . '/dbi/dbdepend.class.php';

/**
 * dbctrl extension (deprecated)
 *
 * @access public
 * @package Database interfaces
 */
class DBI extends DBCtrl {

    var $def;	# Database definitions.

    # <func name="DBI">
    #  <about>Use this constructor to pass db login information.</about>
    # </func>
    function DBI ($dbname, $host, $user, $passwd, $class_dbdef = 0)
    {
        !$class_dbdef ? $this->def = new DBDEPEND : $this->def = $class_dbdef;
        $this->DBCtrl ($dbname, $host, $user, $passwd);
    }

    #########################
    ### Column operations ###
    #########################

    # Return column of a row.
    # table:  Table name
    # id:     Row's primary key
    # column: Column name
    function column ($table, $column, $id)
    {
        if (!$pri = $this->def->primary ($table))
	    die ("dbi::column(): No primary key specified for table '$table'.");

        return $this->select ($column, $table, "$pri='$id'")->get ($column);
    }

    ######################
    ### Row operations ###
    ######################

    # Create an empty row in $table.
    function create_row ($table, $pre = 0)
    {
        $def =& $this->def;
        $pri = $def->primary ($table);

        $hash = $def->types ($table);
        if (!is_array ($hash))
	    die ("dbi::create_row(): No table '$table' defined.");

        # Check names of preset values.
        if (is_array ($pre)) {
            foreach ($hash as $f)
                $names[$f['n']] = true;
            foreach ($pre as $n => $tmp)
                if (!isset ($names[$n]))
                    die ("No such field '$n' in table '$table'.");
        }

        $set = '';
        foreach ($hash as $v) {
            $n = $v['n'];
            if (!isset ($pre[$n]) || isset ($v['readonly']) || $n == $pri)
                continue;
	    $set = sql_append_assignment ($set, $n, $pre[$n]);
        }
        $this->insert ($table, $set);
    }

    ########################
    ### Table operations ###
    ########################

    # Note: This function needs a table definition (see define_table ()).
    function create_table ($table)
    {
        $t = (substr ($table, 0, 4) == '_wrk') ?
             substr ($table, 4, strlen ($table) - 4) :
             $table;
        $this->_create_table ($this->def, $table, $this->table_prefix ());
    }

    function drop_table ($name)
    {
        $this->query ("DROP TABLE $prefix$name");
    }

    ###########################
    ### Database operations ###
    ###########################

    # Create all defined tables.
    function create_tables ($table_prefix = '')
    {
        foreach ($this->def->table_names () as $table)
            $this->_create_table ($this->def, $table, $table_prefix);
    }

    function lock_tables ()
    {
        $def =& $this->def;

        $types = $def->types ();
        $tmp = reset ($types);
        $q = '';
        while ($tmp) {
            $tab = key ($types);
	    $q .= "$tab WRITE, _wrk$tab WRITE";
	    $tmp = next ($types);
	    if ($tmp)
	        $q .= ', ';
        }
        $this->query ("LOCK TABLES $q");
    }

    function unlock_tables ()
    {
        $this->query ('UNLOCK TABLES');
    }

    # Create a new working copy from an existing table set.
    function copy_all_tables ($frompre, $topre)
    {
        $this->create_all_tables ($topre);
        $this->lock_tables ();
        foreach ($this->def->types () as $tab) {
	    $this->query ("DELETE FROM $topre$tab");
	    $this->query ("INSERT INTO $topre$tab SELECT * FROM $frompre$tab");
        }
        $this->unlock_tables ();
    }

    #######################################
    ### Operations with multiple tables ###
    #######################################

    # Note: This is a private method and subject to change without notice.
    function _delete_update_siblings ($table, $id)
    {
        $def =& $this->def;

        if (!$def->is_list ($table))
            return;

        $c_id = $def->primary ($table);
        $c_last = $def->prev_of ($table);
        $c_next = $def->next_of ($table);

        if (!$res = $this->select ("$c_last,$c_next", $table, "$c_id=$id"))
            return;
        list ($id_last, $id_next) = $res->get ();
        if ($id_last)
            $this->update ($table, "$c_next=$id_next", "$c_id=$id_last");
        if ($id_next)
            $this->update ($table, "$c_last=$id_last", "$c_id=$id_next");
    }

    # Delete a row and rows in other tables it points to.
    function multi_delete ($table, $id)
    {
        $def =& $this->def;
        $id = addslashes ($id);

        if (!isset ($def->_refs[$table])) {
	    $this->_delete_update_siblings ($table, $id);
	    $this->delete ($table, $def->primary ($table) . "='$id'");
	    return;
        }
        $info = $def->_refs[$table];
        foreach ($info as $ref) {
            $res = $this->select ($def->primary ($ref['table']), $ref['table'], $ref['id'] . "='$id'");
            if ($res)
                while (list ($id_ref) = $res->get ())
                    $this->multi_delete ($ref['table'], $id_ref);
	    $this->_delete_update_siblings ($table, $id);

	    # Finally remove node.
            $this->delete ($table, $def->primary ($table) . "='$id'");

            if ($xref = $def->xref_table ($table))
                $this->delete ($xref, "id_child=$id");
        }
    }

    # This function iterates from a row to the root of the table hierarchy.
    function traverse_refs_from (&$app, $table, $id, $function, $arguments, $backwards = false)
    {
        $def =& $this->def;

        $out = '';
        foreach ($def->_refs as $parent_table => $ref_tables) {
	    foreach ($ref_tables as $tab) {
	        if ($tab['table'] != $table)
                    continue;
                if (!$res = $this->select ('*', $table, $def->primary ($table) . "=$id"))
                    continue;
                $row = $res->get ();
                $new_id = $row[$tab['id']];
                if ($backwards)
                    $out .= $function ($app, $table, $row, $arguments);
                if ($new_id)
                    $out .= $this->traverse_refs_from ($app, $parent_table, $new_id, $function, $arguments, $backwards);
                if (!$backwards)
                    $out .= $function ($app, $table, $row, $arguments);
                return $out;
	    }
        }
        die ("Nothing referencing table '$table'/id '$id'.");
    }

    # Append new record to end of doubly linked list identified by the
    # parent node in the tree $id_dest_parent.
    function append_new ($table, $pre = 0)
    {
        if (!$pre)
            $pre = array ();

        $def =& $this->def;

        if (!$def->types ($table))
            die ("dbi::append_new(): Table $table isn't defined.");

        # Get column names.
        $id = $def->primary ($table);
        $id_last = $def->prev_of ($table);
        $id_next = $def->next_of ($table);
        $id_parent = $def->ref_id ($table);

/*
        # Get destination list's parent record if required.
        if ($id_parent) {
            if (!$id_dest_parent)
                $id_dest_parent = $this->column ($table, $id_parent, $id_next);
            if (!isset ($pre[$id_parent]))
                $pre[$id_parent] = $id_dest_parent;
        }
*/

        # Do simple append if no list or list references are overridden.
        if (!$def->is_list ($table) || isset ($pre[$id_last]) || isset ($pre[$id_next])) {
            $this->create_row ($table, $pre);
	    return $this->insert_id ();
        }

        # Get id of last record in list.
        $q = "$id_next=0";
        if ($id_parent)
            $q .= " AND $id_parent=" . $pre[$id_parent];
        if ($res = $this->select ($id, $table, $q))
            list ($last) = $res->get ();
        else
          $last = 0;

        # Insert new element and let it point to the last one.
        $pre[$id_last] = $last;
        $pre[$id_next] = 0;
        $this->create_row ($table, $pre);

        # Let previous element point to ours.
        $nid = $this->insert_id ();
        if ($last)
            $this->update ($table, "$id_next=$nid", "$id=$last");

/*
        if ($xref = $def->xref_table ($table))
            $this->insert ($xref, "id_parent=$id_dest_parent, id_child=$nid");
*/

        return $nid;
    }

    function move ($table, $id, $id_next = '0', $id_parent = '0')
    {
        $def =& $this->def;

        if ($table == $def->ref_table ($table) && ($id == $id_parent || $id == $id_next || ($id_parent && $id_parent == $id_next)))
            return true; # Would cause record to disappear somewhere in the db.

        $c_last = $def->prev_of ($table);
        $c_next = $def->next_of ($table);
        $c_id = $def->primary ($table);
        $c_id_parent = $def->ref_id ($table);

        # Read in tree nodes.
        $fields = "$c_id";
        if ($c_last && $c_next)
            $fields .= ", $c_last, $c_next";
        if ($c_id_parent)
            $fields .= ", $c_id_parent";
 
        # XXX Aua!
        $res = $this->select ($fields, $table);
        while ($res && $r = $res->get ()) {
            if ($c_id_parent && !$r[$c_id_parent])
                $r[$c_id_parent] = '0';
            $nodes[$r[$c_id]] = $r;
        }
        $row = $nodes[$id];
        if ($c_id_parent && !$id_parent) {
            if (!$id_next)
                die ('dbi::move(): No destination specified.');
            $id_parent = $nodes[$id_next][$c_id_parent];
        }

        if ($xref = $def->xref_table ($table))
            list ($id_srcparent) = $db->select ('id_parent', $xref, 'id_child=' . $row['id_child'])->get ();

        # Check if the record has no siblings and the destination has the
        # same parent. If so, don't move anything.
        if ($id_parent == $nodes[$id][$c_id_parent] && $c_last && $c_next && !$nodes[$id][$c_last] && !$nodes[$id][$c_next])
            return true;

        if ($c_next) {
            if ($row[$c_next] == $id_next)
	        return; # Nothing to do.

	    # Remove record from list.
	    if ($row[$c_last]) {
	        $this->update ($table, "$c_next=" . $row[$c_next], "$c_id=" . $row[$c_last]);
	        $nodes[$row[$c_last]][$c_next] = $row[$c_next];
	    }
	    if ($row[$c_next]) {
	        $this->update ($table, $c_last . '=' . $row[$c_last], $c_id . '=' . $row[$c_next]);
	        $nodes[$row[$c_next]][$c_last] = $row[$c_last];
	    }

	    # Update references in new siblings.
	    if ($id_next) {
	        if (!$next = $nodes[$id_next])
	            die ("No next of id $id_next.");
	        if ($c_id_parent && $id_parent != $next[$c_id_parent])
	            $id_parent = $next[$c_id_parent];
	        if ($next[$c_last])
	            $this->update ($table, "$c_next=$id", "$c_id=" . $next[$c_last]);
	        $this->update ($table, "$c_last=$id", "$c_id=" . $next[$c_id]);
	        $last = $next[$c_last];
	        $next = $next[$c_id];
            } else {
	        # Append to end of list.
	        $last = '0';
	        $next = '0';
	        $q = "$c_next=0 AND $c_id!=$id";
	        if ($c_id_parent)
	            $q .= " AND $c_id_parent=$id_parent";
	        if ($res = $this->select ($c_id, $table, $q)) {
	            list ($last) = $res->get ();
	            $this->update ($table, "$c_next=$id", "$c_id=$last");
	        }
            }
            $this->update ($table, "$c_last=$last,$c_next=$next", "$c_id=$id");
        }

        # Update reference to new parent.
        if ($xref)
            $this->update ($xref, "id_parent=$id_parent", "id_parent=$id_srcparent AND id_child=$id");
        else if ($c_id_parent)
            $this->update ($table, "$c_id_parent=$id_parent", "$c_id=$id");
      }
}
?>

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
	    die_traced ("No primary key specified for table '$table'.");

        return $this->select ($column, $table, "$pri='$id'")->get ($column);
    }


    ######################
    ### Row operations ###
    ######################

    # Create an empty row in $table.
    function create_row ($table, $preset_values = 0)
    {
        $def =& $this->def;
        $pri = $def->primary ($table);

        if (!$types = $def->types ($table))
	    die_traced ("No table '$table' defined.");

        # Check names of preset values.
        if ($preset_values) {
            $names = $def->field_names ($table);
            foreach ($preset_values as $n => $dummy)
                if (!array_search ($n, $names))
                    die_traced ("No such field '$n' in table '$table'.");
        }

        $this->insert ($table, sql_assignments ($preset_values));
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
        $c_last = $def->id_prev ($table);
        $c_next = $def->id_next ($table);

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
        die_traced ("Nothing referencing table '$table'/id '$id'.");
    }

    # Append new record to end of doubly linked list identified by the
    # selection in $pre.
    function append_new ($table, $values = 0, $pre = 0)
    {
        if (!$values)
            $values = array ();
        if (!$pre)
            $pre = array ();

        $def =& $this->def;

        if (!$def->types ($table))
            die_traced ("Table $table isn't defined.");

        # Get column names.
        $id = $def->primary ($table);
        $id_last = $def->id_prev ($table);
        $id_next = $def->id_next ($table);

        # Do simple append if no list or list references are overridden.
        if (!$def->is_list ($table) || isset ($values[$id_last]) || isset ($pre[$id_last]) || isset ($values[$id_next]) || isset ($pre[$id_next])) {
            $this->create_row ($table, $pre);
	        return $this->insert_id ();
        }

        if ($id_parent = $def->id_parent ($table))
            if (!isset ($pre[$id_parent]))
                die_traced ("Preset reference '$id_parent' to parent table missing.");

        # Get id of last record in list.
        $q = array_merge ($pre, array ($id_next => 0));
        $last = ($res = $this->select ($id, $table, sql_selection_assignments ($pre))) ?
                $res->get ($id) :
                0;

        $pre[$id_last] = $last;
        $pre[$id_next] = 0;
        $this->create_row ($table, array_merge ($pre, $values));

        # Let previous element point to ours.
        $nid = $this->insert_id ();
        if ($last)
            $this->update ($table, "$id_next=$nid", array_merge ($pre, array ($id => $last)));

        return $nid;
    }

    function move ($table, $id, $id_next = '0', $id_parent = '0')
    {
        $def =& $this->def;

        if ($table == $def->ref_table ($table) && ($id == $id_parent || $id == $id_next || ($id_parent && $id_parent == $id_next)))
            die_traced ("Cannot move - record corrupted.");

        $c_last = $def->id_prev ($table);
        $c_next = $def->id_next ($table);
        $c_id = $def->primary ($table);
        $c_id_parent = $def->id_parent ($table);

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
                die_traced ('No destination specified.');
            $id_parent = $nodes[$id_next][$c_id_parent];
        }

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
	            die_traced ("No next of id $id_next.");
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
            $this->update ($table, "$c_last=$last, $c_next=$next", "$c_id=$id");
        }

        # Update reference to new parent.
        if ($c_id_parent)
            $this->update ($table, "$c_id_parent=$id_parent", "$c_id=$id");
      }
}

?>

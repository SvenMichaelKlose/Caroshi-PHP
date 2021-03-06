<?php

/**
 * Stepping through SQL trees
 *
 * @access public
 * @module dbtree
 * @package Database interfaces
 */


# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


$_DBITREE_CACHE_PARENT = array ();

# Get table name and primary key of a parent record.
function dbitree_get_parent ($db, &$table, &$id)
{
    global $_DBITREE_CACHE_PARENT;

    $def = $db->def;

    if (isset ($_DBITREE_CACHE_PARENT[$table][$id])) {
        list ($table, $id) = $_DBITREE_CACHE_PARENT[$table][$id];
        return;
    }

    $id_parent = $def->id_parent ($table);
    if (!$res = $db->select ($id_parent, $table, "id=$id")) {
        $table = $id = 0;
        return;
    }

    $parent_id = $res->get ($id_parent);
    $parent_table = $def->ref_table ($table);
    $_DBITREE_CACHE_PARENT[$table][$id] = array ($parent_table, $parent_id);
    $table = $parent_table;
    $id = $parent_id;
}

function find_in_database_path_if ($db, $table, $id, $fun)                                                                                                     
{
    $dep = $db->def;

    while ($table && $id) {
        if (!$dep->ref_table ($table))
            die_traced ("No reference to parent defined for table '$table' (use dbdepend::set_ref()).");

        if (!$pri = $dep->primary ($table))
            die_traced ("No primary key defined for table '$table' (use dbdepend::set_primary()).");

        $row = $db->select ('*', $table, "$pri=$id")->get ();

        if ($result = $fun ($table, $id, $row))
            return $result;

        dbitree_get_parent ($db, $table, $id);
    }
}

function dbtree_get_object_id ($db, $table, $id)
{
    $res = $db->select ('*', $table, "id=$id");
    return $res ? $res->get ('id_obj') : null;
}

function dbtree_get_objects_by_id ($db, $id)
{
    if ($res = $db->select ('*', 'obj_data', "id_obj=$id")) {
        while ($row = $res->get ())
            $objects[] = $row;
        return $objects;
    }
    return Array ();
}

function dbtree_get_objects_for_record ($db, $table, $id)
{
     if ($id_obj = dbtree_get_object_id ($db, $table, $id))
         return dbtree_get_objects_by_id ($db, $id_obj);
    return Array ();
}

# Read all objects along the path to the root.
#
# Returns a hash of object lists for each class.
function dbtree_get_objects_in_path ($db, $table, $id)
{
    $t = $table;
    $i = $id;

    $path_objects = array ();
    while ($t && $i) {
        foreach (dbtree_get_objects_for_record ($db, $t, $i) as $row) {
            $row['_table'] = $t;
            $row['_id'] = $i;
            $path_objects[$row['id_class']][] = $row;
        }
        dbitree_get_parent ($db, $t, $i);
    }

    return $path_objects;
}

?>

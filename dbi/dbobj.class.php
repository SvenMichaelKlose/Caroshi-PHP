<?php

# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


# About this file:
#
# Associate object of a particular class to a table entry.
# Inheritance supported when dbdepend is used.
#
# Fetch an existing object associated to existing entry or create a new
# object to associate it later:
#	$obj = new DBOBJ ($db, $class, [$dep, [$table, $id]]);
#
#	$obj = new DBOBJ ($class);
#
# Associate an object to a column, or move it if it's already associated.
# When invoked without arguments, the object contents are written to the
# database.
#	$obj->assoc ([$table, $id]);
#
# Remove object. After removal the object contents can be associated to
# another table entry. This is used by assoc to move data.
#	$obj->remove ();


/**
 * Databased directory service (deprecated)
 *
 * @access public
 * @package Database interfaces
 */
class DBOBJ {
    var $_class;
    var $_db;		# Database connection
    var $_dep;		# dbdepend.class instance
    var $_table = 0;
    var $_id = 0;
    var $_row;
    var $_oid = 0;
    var $_cid = 0;

    var $data;

    # Initialize and fetch object if exists.
    function DBOBJ (&$db, $class, &$dep, $table = '', $id = 0, $fields = '*')
    {
        global $__DBOBJ_CLASSCACHE;

        $this->_db =& $db;
        $this->_class = $class;
        $this->_dep =& $dep;
        $this->_table = $table; # Remember starting point.
        $this->_id = $id;

        # Get class id, update cache if class is not found.
        if (!sizeof ($__DBOBJ_CLASSCACHE)) {
            # Read all classes into the cache.
            if (!$cres = $db->select ('id,name', 'obj_classes'))
                return;
	    while ($tmp = $cres->get ())
	        $__DBOBJ_CLASSCACHE[$tmp['name']] = $tmp['id'];
        }
        if (!isset ($__DBOBJ_CLASSCACHE[$class]))
	    return;

        # Force fetching field 'is_local'.
        if ($fields != '*')
	    $fields .= ', is_local';

        $cid = $this->_cid = $__DBOBJ_CLASSCACHE[$class];
        $t =& $this;
        find_in_database_path_if ($db, $table, $id,
            function ($table, $id, $row) use (&$t, &$db, &$dep, &$fetch_local, &$found_local, $cid, $fields)
            {
                global $__DBOBJ_KEYCACHE, $__DBOBJ_CLASSCACHE, $__DBOBJ_DATACACHE;

	        if (!$oid_field = $dep->obj_id ($table))
                    return;

	        # Fetch table entry.
	        if (!isset ($__DBOBJ_KEYCACHE[$table][$id]))
	            $t->_row =& $__DBOBJ_KEYCACHE[$table][$id];
	        else
	            $__DBOBJ_KEYCACHE[$table][$id] = $t->_row = $db->select ('*', $table, "$pri=$id")->get ();

                $row =& $t->_row;

                # Skip entry if object id is 0.
	        if (!$oid = $row[$oid_field])
                    return;

      	        # Seek data for id_obj/class combination if it's not in the cache.
	        if (!isset ($__DBOBJ_DATACACHE[$oid][$cid][$fields]))
	            if ($dres = $db->select ($fields, 'obj_data', "id_obj=$oid AND id_class='$cid'"))
	                $__DBOBJ_DATACACHE[$oid][$cid][$fields] = $dres->get ();

                # Use object if it's in the cache now.
      	        if (!isset ($__DBOBJ_DATACACHE[$oid][$cid][$fields]))
                    return;

      	        $tmp =& $__DBOBJ_DATACACHE[$oid][$cid][$fields];
	        $tmp['found_local'] = ($t->_table == $table && $t->_id == $id);
	        $tmp['_table'] = $t->_table = $table;
	        $tmp['_id'] = $t->_id = $id;
	        $t->data = $tmp;

                return true;
            }
        );
    }

    function _make_object_id_if_not_exists ($table, $id)
    {
        $dep =& $this->_dep;
        $db =& $this->_db;

        if (!$res = $db->select ($dep->obj_id ($table), $table, $dep->primary ($table) . "=$id")) {
	    $db->insert ('objects', 'dummy=0');  
	    $this->_oid = $db->insert_id ();
	    $db->update ($table, "id_obj=$this->_oid", "id=$id");
        } else
            list ($this->_oid) = $res->get ();
    }

    # Associate object to table/id.
    # Move already associated objects.
    # Write new object contents to database (no args).
    function assoc ($table = '', $id = 0)
    {
        type_array ($this->data);
        if (!$table || !$id)
             die_traced ('Empty arguments.');

        $dep =& $this->_dep;
        $db =& $this->_db;

        if ($this->_table == $table && $this->_id == $id)
            die_traced ('Object already at position specified in arguments.');

        if ($this->_table && $this->_id)
            $this->remove ();

        $this->_make_object_id_if_not_exists ($table, $id);

        $tmp = $this->data;
        unset ($tmp['found_local']);
        unset ($tmp['_table']);
        unset ($tmp['_id']);
        unset ($tmp['id']);
        $set = sql_assignments ($tmp);
        $set .= ($set ? ', ' : '') . 'id_obj=' . $this->_oid . ', id_class=' . $this->_cid;
        $db->insert ('obj_data', $set);
        $this->data['id'] = $db->insert_id ();

        DBOBJ::_drop_cache ();
    }

    # Remove an object.
    function remove ()
    {
        $db = $this->_db;
        $dep = $this->_dep;

        DBOBJ::_drop_cache ();
        if (!$this->_class || !$this->data['_table'] || !$this->data['_id'])
            die_traced ('Object doesn\'t exist.');

        # Remove this data object from the database
        $db->delete ('obj_data', 'id=' . $this->data['id']);

        # Remove object reference and entry in table 'objects' if there're
        # no more referenced objects.
        if (!$db->select ('id', 'obj_data', "id_obj=$this->_oid")) {
            $db->delete ('objects', "id=$this->_oid");
	    $table = $this->_table;
	    $id = $this->_id;
            $db->update ($table, $dep->obj_id ($table) . '=0', $dep->primary ($table) . "=$id");
        }

        # Now the object can be copied to another table; used by assoc ().
        $this->_table = $this->_id = $this->_oid = 0;
    }

    static function _drop_cache ()
    {
        global $__DBOBJ_KEYCACHE, $__DBOBJ_CLASSCACHE, $__DBOBJ_DATACACHE;

        $__DBOBJ_KEYCACHE = array ();
        $__DBOBJ_CLASSCACHE = array ();
        $__DBOBJ_DATACACHE = array ();
    }

    static function define_tables (&$def)
    {
        $def->define_table (
  	    'objects',
            array (
                array ('n' => 'id',
                       't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                       'd' => 'primary key'),
                array ('n' => 'dummy',
                       't' => 'INT NOT NULL',
                       'd' => 'Dummy field')
	    )
        );
        $def->set_primary ('objects', 'id');

        $def->define_table (
  	    'obj_classes',
            array (
                array ('n' => 'id',
                       't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                       'auto_form' => array ('hide' => true),
                       'd' => 'primary key'),
                array ('n' => 'name',
                       't' => 'VARCHAR(255) NOT NULL',
		       'i' => true,
                       'd' => 'Class name'),
                array ('n' => 'descr',
                       't' => 'VARCHAR(255) NOT NULL',
                       'd' => 'Class description')
	    )
        );
        $def->set_primary ('obj_classes', 'id');

        $def->define_table (
  	    'obj_data',
            array (
                array ('n' => 'id',
                       't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                       'd' => 'primary key'),
                array ('n' => 'id_obj',
                     't' => 'INT NOT NULL',
		       'i' => true,
                       'd' => 'Reference to object'),
                array ('n' => 'id_class',
                       't' => 'INT NOT NULL',
		       'i' => true,
                       'd' => 'Reference to class'),
                array ('n' => 'start',
                       't' => 'INT NOT NULL',
		       'i' => true,
                       'd' => 'Start time'),
                array ('n' => 'end',
                       't' => 'INT NOT NULL',
		       'i' => true,
                       'd' => 'End time'),
                array ('n' => 'type',
                       't' => 'INT NOT NULL',
                       'd' => 'Internal object type (unused?)'),
                array ('n' => 'mime',
                       't' => 'VARCHAR(255) NOT NULL',
                       'd' => 'MIME type'),
                array ('n' => 'filename',
                       't' => 'VARCHAR(255) NOT NULL',
		       'i' => true,
                       'd' => 'Original file name'),
                array ('n' => 'is_local',
                       't' => 'INT NOT NULL',
		       'i' => true,
                       'd' => 'not inheritable?'),
                array ('n' => 'is_public',
                       't' => 'INT NOT NULL',
		       'i' => true,
                       'd' => 'exportable?'),
                array ('n' => 'data',
                       't' => 'MEDIUMTEXT NOT NULL',
                       'd' => 'Object data')
	    )
        );
        $def->set_primary ('obj_data', 'id');
    }
}

DBOBJ::_drop_cache ();

?>

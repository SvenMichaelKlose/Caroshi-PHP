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

    var $active;	# Content of active object found.
    var $inactive;	# Contents of all other objects found.

    # Initialize and fetch object if exists.
    function DBOBJ (&$db, $class, &$dep, $table = '', $id = 0, $only_active = false, $fields = '*')
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

        # Traverse path to root until we've found something.
        $fetch_local = $found_local = true;

        # Force fetching field 'is_local'.
        if ($fields != '*')
	    $fields .= ', is_local';

        $cid = $this->_cid = $__DBOBJ_CLASSCACHE[$class];
        $t =& $this;
        find_in_database_path_if ($db, $table, $id,
            function ($table, $id, $row) use (&$t, &$db, &$dep, &$fetch_local, &$found_local, $cid, $fields, $only_active)
            {
                global $__DBOBJ_KEYCACHE, $__DBOBJ_CLASSCACHE, $__DBOBJ_DATACACHE;

	        if (!isset ($dep->_obj_id[$table]))
	            die_traced ("No object reference defined for table '$table' (use dbdepend::set_obj_id()).");

	        # Fetch table entry.
	        if (!isset ($__DBOBJ_KEYCACHE[$table][$id])) {
                    if (!$pri = $dep->primary ($table))
	                die_traced ("No primary key defined for table '$table' (use dbdepend::set_primary()).");
	            $__DBOBJ_KEYCACHE[$table][$id] = $t->_row = $db->select ('*', $table, "$pri=$id")->get ();
	        } else {
	            $t->_row =& $__DBOBJ_KEYCACHE[$table][$id];
	        }

                $row =& $t->_row;

                # Skip entry if object id is 0.
	        if ($oid = $row[$dep->_obj_id[$table]]) {
      	            # Seek data for id_obj/class combination if it's not in the cache.
	            if (!isset ($__DBOBJ_DATACACHE[$oid][$cid][$fields])) {
	                $__DBOBJ_DATACACHE[$oid][$cid][$fields] =
	                    ($dres = $db->select ($fields, 'obj_data', "id_obj=$oid AND id_class='$cid'")) ?
                                $dres->get () :
                                0;
	            }

                    # Use object if it's in the cache now.
      	            if (isset ($__DBOBJ_DATACACHE[$oid][$cid][$fields]) && is_array ($__DBOBJ_DATACACHE[$oid][$cid][$fields])) {
      	                $tmp =& $__DBOBJ_DATACACHE[$oid][$cid][$fields];

	                # Read in object and return with the first one that's
	                # visible. Ignore local objects which are not at our current
	                # position.
	                if (isset ($tmp['is_local']) && !($tmp['is_local'] && !$fetch_local)) {
	                    # Update active/inactive result set.
	                    # Add table and id and 'found_local' flag that shows if object
	                    # was found at the starting point.
	                    $tmp['found_local'] = $found_local;
	                    $tmp['_table'] = $t->_table = $table;
	                    $tmp['_id'] = $t->_id = $id;
	                    if (!isset ($t->active) || !is_array ($t->active)) {
	                        $t->_oid = $oid;
	                        $t->active =& $tmp;
	                        if ($only_active)
	                            return true;
	                    } else
	                        $t->inactive[] =& $tmp;
                        }
	            }
                }
                $fetch_local = $found_local = false;
            }
        );
    }

    # Associate object to table/id.
    # Move already associated objects.
    # Write new object contents to database (no args).
    function assoc ($table = '', $id = 0)
    {
        $dep =& $this->_dep;
        $db =& $this->_db;

        if (!is_array ($this->active))
            die ('No contents for object.');
        if (!$table || !$id) {
            if (!$this->_table || !$this->_id)
                return; # Can't sync unassociated.
        } else {
            if ($this->_table != $table || $this->_id != $id) {
                # Remove old reference in directory if we're going to move.
                if ($this->_table && $this->_id)
                    $this->remove ();

                # Get object id of new table/id pair
	        if ($res = $db->select ($dep->_obj_id[$table], $table, $dep->primary ($table) . "=$id"))
	            list ($this->_oid) = $res->get ();
	        if (!$this->_oid) {  
	            # Create object id for new table/id and store it
	            $db->insert ('objects', 'dummy=0');  

	            # Update object reference.
	            $this->_oid = $db->insert_id ();
	            $db->update ($table, "id_obj=$this->_oid", "id=$id");
	        }
            }
        }

        if (!$this->_oid)
            die ('dbobj::assoc(): Object has no id.');

        # Unset unknown fields.
        $tmp = $this->active;
        unset ($tmp['found_local']);
        unset ($tmp['_table']);
        unset ($tmp['_id']);

        # Create SET clause from array.
        $set = '';
        $first = true;
        if (is_array ($tmp)) {
            foreach ($tmp as $k => $v) {
	        if (!$k || is_int ($k) || $k == 'id')
	            continue;
                if ($first == false)
                    $set .= ', ';
                else
                    $first = false;
	        $set .= "$k='" . addslashes ($v) . "'";
	    }

	    # Make sure there's a comma to append something.
            if (!$first)
	        $set .= ", ";
        }
        $set .= 'id_obj=' . $this->_oid . ', id_class=' . $this->_cid;

        # Write object contents to database.  
        if (isset ($tmp['id'])) {
            $db->update ('obj_data', $set, 'id=' . $tmp['id']);
        } else {
            $db->insert ('obj_data', $set);
            $this->active['id'] = $db->insert_id ();
        }  

        DBOBJ::_drop_cache ();
    }

    # Remove an object.
    # Returns 'true' if successful.
    function remove ()
    {
        $db = $this->_db;
        $dep = $this->_dep;

        DBOBJ::_drop_cache ();
        if (!$this->_class || !$this->active['_table'] || !$this->active['_id'])
            return false; # Object doesn't exist.

        # Remove this data object from the database
        $db->delete ('obj_data', 'id=' . $this->active['id']);

        # Remove object reference and entry in table 'objects' if there're
        # no more referenced objects.
        if (!$db->select ('id', 'obj_data', "id_obj=$this->_oid")) {
            $db->delete ('objects', "id=$this->_oid");
	    $table = $this->_table;
	    $id = $this->_id;
            $db->update ($table, $dep->_obj_id[$table] . '=0', $dep->primary ($table) . "=$id");
        }

        # Now the object can be copied to another table; used by assoc ().
        $this->_table = $this->_id = $this->_oid = 0;

        return true;
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

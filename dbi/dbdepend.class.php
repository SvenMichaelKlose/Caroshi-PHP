<?php

# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

/**
 * SQL database description.
 *
 * @access public
 * @package Database interfaces
 * @author Sven Michael Klose <pixel@copei.de>
 */
class DBDEPEND {
    var $_definitions;
    var $_ref_table;
    var $_id_parent;
    var $_obj_id;
    var $table;

    var $_fields;   # Field names.
    var $_types;    # Field names and SQL types of registered tables
    var $_exttypes; # Field names and extended types of registered tables
    var $_desc;	    # Human-readable field description, e.g. 'Phone'.

    var $_refs;
    var $_listrefs;

    var $_primaries; # Primaries of tables.

    /**
     * Define columns of a table.
     *
     * The field types are described by array over array that hold various
     * information about each column:
     *
     * array (array ('field_info' => 'data', # Field A
     *               'field_info' => 'data'),
     *        array ('field_info' => 'data', # Field B
     *               'field_info' => 'data'),
     *        #...
     *        );
     *
     * Each column description must contain the following field infos:
     *
     *   'n' - Name
     *   't' - SQL type
     *
     * Optional:
     *
     *   'i' - Index is created for faster lookups if true.
     *   'e' - Extended type from dbi/types.php
     *
     * @access public
     * @param string $table  Table name.
     * @param array  $fields Array of field descriptions.
     */
    function define_table ($table, $fields)
    {
        if (isset ($this->_types[$table]))
            die_traced ("Table $table already exists.");

        # Fill up hashes we can access fast.
        foreach ($fields as $field) {
            # Check if info is complete.
	    if (!isset ($field['n']))
	        die_traced ('Field without a name.');
	    if (!isset ($field['t']))
	        die_traced ('Field without a SQL type.');

            # Default extended type is 'string'.
	    if (!isset ($field['e']))
	        $field['e'] = 'string';

	    $name = $field['n'];
	    $type = $field['t'];
	    $desc = isset ($field['d']) ? $desc = $field['d'] : '';
	    $this->_definitions[$table][$name] = $field;
	    $this->_fields[$table][] = $name;
	    $this->_types[$table][$name] = $type;
	    $this->_desc[$table][$name] = $desc;
	    $this->_exttypes[$table][$name] = $field;
        }
    }

    function definition ($table)
    {
        return $this->_definitions[$table];
    }

    function table_names ()
    {
        foreach ($this->_types as $name => $dummy)
            if (!is_numeric ($name))
                $names[] = $name;
        return $names;
    }

    function field_names ($table)
    {
        return $this->_fields[$table];
    }

    /**
     * Return array of extended field description of a $table.
     *
     * @access public
     * @param string $table Table name.
     * @returns array Array of types for each field keyed by name. If the
     *                table name is empty, an array of all type arrays is
     *                returned which is keyed by table names. 
     */
    function types ($table = '')
    {
        if (!$table)
            return $this->_exttypes;
        if (isset ($this->_exttypes[$table]))
            return $this->_exttypes[$table];
    }

    /**
     * Define a cross-table reference.
     *
     * When using multi-delete, all records that point to a record which is
     * deleted, they are also removed.
     * This way you can create a tree structure by letting a table point to
     * itself.
     *
     * @access public
     * @param string $table
     *        Name of table that holds the reference.
     * @param string $id_parent
     *        Name of the column that holds the primary key's value of the
     *        referenced row.
     * @param string $ref_table
     *        Name of the table that is referenced.  The table's primary key
     *        must be defined using set_primary().
     */
    function set_ref ($table, $ref_table, $id_parent)
    {
        $this->_chktbl ($table);

        $this->_refs[$table][] = array ('table' => $ref_table, 'id' => $id_parent);
        $this->_ref_table[$ref_table] = $table;
        $this->_id_parent[$ref_table] = $id_parent;
        $this->table[$table][] = $ref_table;
    }

    /**
     * Get name of a parent table.
     *
     * @access public
     * @param string $table Table name.
     * @returns string Name of the parent table.
     */
    function ref_table ($table)
    {
        if (isset ($this->_ref_table[$table]))
            return $this->_ref_table[$table];
    }

    /**
     * Get name of field with reference to a parent record.
     *
     * @access public
     * @param string $table Table name.
     * @returns string Name of the field in $table.
     */
    function id_parent ($table)
    {
        if (isset ($this->_id_parent[$table]))
            return $this->_id_parent[$table];
    }

    /**
     * Define the reference fields to siblings of rows in a table.
     *
     * @access public
     * @param string $table   The table name.
     * @param string $id_last Column name that holds the primary key of the
     *                        previous row.
     * @param string $id_next Column name that holds the primary key of the
     *                        next row.
     */
    function set_listref ($table, $id_last, $id_next)
    {
        $this->_chktbl ($table);

        $this->_listrefs[$table]['last'] = $id_last;
        $this->_listrefs[$table]['next'] = $id_next;
    }

    /**
     * Determine if a table holds lists (defined using set_listref ())
     *
     * @access public
     * @param string $table   The table name.
     */
    function is_list ($table)
    {
        return isset ($this->_listrefs[$table]);
    }

    /**
     * Define object ID.
     *
     * @access public
     * @param string $table  The table name.
     * @param string $name   The field name.
     */
    function set_obj_id ($table, $name)
    {
        $this->_chktbl ($table);
        $this->_obj_id[$table] = $name;
    }

    /**
     * Get name of  object ID field.
     *
     * @access public
     * @param string $table The table name.
     * @returns string      The field name.
     */
    function obj_id ($table)
    {
        $this->_chktbl ($table);
        if (isset ($this->_obj_id[$table]))
            return $this->_obj_id[$table];
    }

    /**
     * Get name of field that points to the previous row in a list.
     *
     * @access public
     * @param string $table The table name.
     * @returns string Field name.
     */
    function id_prev ($table)
    {
        if (isset ($this->_listrefs[$table]['last']))
            return $this->_listrefs[$table]['last'];
    }

    /**
     * Get name of field that points to the next row in a list.
     *
     * @access public
     * @param string $table The table name.
     * @returns string Field name.
     */
    function id_next ($table)
    {
        if (isset ($this->_listrefs[$table]['next']))
            return $this->_listrefs[$table]['next'];
    }

    /**
     * Define field name for a table's primary key.
     *
     * @access public
     * @param string $table   The table name.
     * @param string $primary Primary field name.
     */
    function set_primary ($table, $primary)
    {
        $this->_chktbl ($table);
        if (isset ($this->_primaries[$table]))
            die_traced ("Primary key for table $table already exists.");

        $this->_primaries[$table] = $primary;
    }

    /**
     * Get field name of primary key.
     *
     * @access public
     * @param string $table The table name.
     * @returns string      Primary field name.
     */
    function primary ($table)
    {
        if (isset ($this->_primaries[$table]))
            return $this->_primaries[$table];
    }

    /**
     * Check if a table is defined.
     *
     * @access private
     * @param string $table The table name.
     * @param string $func  Function name to show if function dies.
     */
    function _chktbl ($table)
    {
        if (!isset ($this->_types[$table]))
            die_traced ("Table $table isn't defined.");
    }
}

?>

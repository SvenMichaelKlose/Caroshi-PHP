<?php
# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once PATH_TO_CAROSHI . '/dbi/dbwrapper.class.php';

/**
 * Common SQL interface
 *
 * Applications communicate with SQL databases of different flavours
 * transparently through this class.
 *
 * @access public
 * @package Database interfaces
 */
class DBCtrl extends DBWrapper {
    private $db_name;
    private $prefix;

    /**
     * Set up a database connection or reuse an existing one.
     *
     * @access public
     * @param string dbname Name of the database to connect to.
     * @param string host   Host which to connect.
     * @param string user   User name.
     * @param string passwd Password.
     */
    function DBCtrl ($dbname, $host, $user, $passwd)
    {
         $this->db_name = $dbname;
         $this->prefix = '';
         $this->DBWrapper ($dbname, $host, $user, $passwd);
    }

    /**
     * Select a result set from database.
     *
     * This function generates a query of the form
     * "SELECT $what FROM $table WHERE $where $tail".
     *
     * @access public
     * @param string dbname Name of the database to connect to.
     * @param string what  Field name or comma separated list of field names.
     *			   an asterisk '*' selects all fields.
     * @param string table Table name to select from.
     * @param string where WHERE clause without WHERE keyword.
     * @param string tail  Tail to SQL query.
     * @return object db_result Returns false if result is empty.
     */
    function select ($what, $table, $where = '', $tail = '')
    {
        $q = "SELECT $what FROM $this->prefix$table";
        if ($where)
            $q .= " WHERE $where";
        if ($tail)
            $q .= " $tail";
        $res = $this->query ($q);
        return $res->num_rows () ? $res : null;
    }

    /**
     * Update rows in a table.
     *
     * This function generates a query of the form
     * "UPDATE $table SET $set WHERE $where".
     *
     * @access public
     * @param string table Table name.
     * @param string set   Field set without SET clause.
     * @param string where WHERE clause without WHERE keyword.
     * @return object db_result
     */
    function update ($table, $set, $where)
    {
        $q = "UPDATE $this->prefix$table SET $set";
        if (!$where)
            die_traced ("Need where clause. $q");
        $q .= " WHERE $where";
        return $this->query ($q);
    }

    /**
     * insert a new row.
     *
     * This function generates a query of the form
     * "UPDATE $table SET $set WHERE $where".
     *
     * @access public
     * @param string table Table name.
     * @param string set   Field set without SET clause.
     * @param string where WHERE clause without WHERE keyword.
     * @return object db_result
     */
    function insert ($table, $set)
    {
        return $this->query ("INSERT INTO $this->prefix$table SET $set");
    }

    /**
     * Remove row(s) from a table.
     *
     * This function generates a query of the form
     * "DELETE FROM $table WHERE $where".
     *
     * @access public
     * @param string table Table name.
     * @param string where WHERE clause without WHERE keyword.
     * @return object db_result
     */
    function delete ($table, $where = '')
    {
        $q = "DELETE FROM $this->prefix$table";
        if ($where)
            $q .= " WHERE $where";
        return $this->query ($q);
    }

    /**
     * Set prefix for all table names used afterwards.
     *
     * @access public
     * @param string table Table name.
     */
    function set_table_prefix ($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Get table prefix used for all table names.
     *
     * @access public
     * @return string table Table name.
     */
    function table_prefix ($table = '')
    {
        if (!$this->prefix)
            return $table;
        return $this->prefix . $table;
    }
}
?>

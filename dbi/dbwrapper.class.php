<?php
# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once PATH_TO_CAROSHI . '/dbi/dbresult.class.php';

# Number of queries run by this class since contruction.
$DB_QUERIES = 0;

/**
 * A wrapper for derived classes to access a MySQL database.
 *
 * This is the reference implementation for vendor-dependent database
 * wrappers. To write wrappers for other databases than MySQL take care
 * to use this class layout.
 *
 * @access private
 * @package Database interfaces
 * @copyright dev/consulting GmbH
 * @author    Sven Michael Klose
 */
class DBWrapper {
    # Private vars.
    var $_db;
    var $_db_name;
    var $_last_result;

    /**
     * Connect to a database.
     *
     * @access public
     * @param string dbname Database name.
     * @param string host   Database host.
     * @param string user   User name.
     * @param string passwd Password.
     */
    function DBWrapper ($dbname, $host, $user, $passwd)
    {
        $this->_db = mysql_pconnect ($host, $user, $passwd);
        mysql_select_db ($dbname, $this->_db);
    }

    /**
     * Create a database.
     *
     * @access public
     * @param name Database name
     */
    function create_db ($name)
    {
        return mysql_create_db ($name);
    }

    /**
     * Drop a database.
     *
     * @access public
     * @param name Database name
     */
    function drop_db ($name)
    {
        return mysql_drop_db ($name);
    }

    /**
     * Create a table based on dbdepend object.
     *
     * @access public
     * @param object dbdepend
     * @param string table  Table name.
     * @param string prefix Table name.prefix to use.
     */
    function _create_table ($dep, $table, $prefix)
    {
        if (!$fields = $dep->types ($table))
            panic ('dbwrapper::create_table(): $table is undefined.');

        $query = '';
        $tail = '';
        foreach ($fields as $name => $field) {
            # Check if info is complete.
	    if (!isset ($field['n']))
	        panic ('dbwrapper::create_table(): field without a name.');
	    if (!isset ($field['t']))
	        panic ('dbwrapper::create_table(): field without a SQL type.');

	    if ($query)
	        $query .= ', ';
	    $query .= $field['n'] . ' ' . $field['t'];

            # Create index if wanted.
            if (isset ($field['i']))
                $tail .= ", KEY($name)";
        }

        $this->query ("CREATE TABLE $prefix$table ($query$tail)");
    }

    /**
     * Perform a SQL query.
     *
     * @access public
     * @param string query SQL query string
     * @return object db_result
     */
    function query ($query)
    {
        global $debug;

        if ($debug) {
	    $GLOBALS['DB_QUERIES']++;
            if ($debug & 2) {
	        echo '<font size="-1" color="blue">' . htmlentities ($query) . "</font>\n";
	        flush ();
	        $t = gettimeofday ();
                $start = $t['usec'] + $t['sec'] * 1000000;
            }
        }

        $this->_last_result = new DB_result (mysql_query ($query, $this->_db));

        if ($m = $this->error ()) {
            if (!$debug)
                echo '<font size="-1" color="blue">' . htmlentities ($query) . "</font>\n";
            echo '<font color="red" size="-1">' . $m . "</font><BR>\n";
        }
        if ($debug) {
            if ($debug & 2) {
	        $t = gettimeofday ();
                echo ($t['usec'] + $t['sec'] * 1000000 - $start) / 1000000 . "s<br>\n";
            }
        }

        return $this->_last_result;
    }

    /**
     * Return primary key value of last inserted record.
     *
     * @access public
     * @return string
     */
    function insert_id ()
    {
        return mysql_insert_id ();
    }

    /**
     * Close the database connection.
     *
     * @access public
     * @return boolean
     */
    function close ()
    {
        return mysql_close ();
    }

    /**
     * Return error string of last operation.
     *
     * @access public
     * @return string An empty string is returned if no error occured.
     */
    function error ()
    {
        return mysql_error ();
    }

    /**
     * Check if database connection was established.
     *
     * @access public
     * @return boolean True if we're connected to a database, false otherwise.
     */
    function is_connected ()
    {
        return $this->_db ? 1 : 0;
    }
}
?>

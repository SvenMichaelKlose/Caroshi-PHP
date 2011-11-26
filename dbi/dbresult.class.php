<?php
# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


/**
 * Datbase result object.
 *
 * This class represents results returned by DBWrapper::query ().
 *
 * @access public
 * @package Database interfaces
 */
class DB_result {
    private $res;

    /**
     * Constructor for use of DBWrapper only.
     *
     * Calling this constructor is allowed for database wrappers only.
     *
     * @access private
     * @param mixed res Result set of internal type.
     */
    function DB_result ($res) { $this->res = $res; }

    /**
     * Fetch first/next row or field from result set.
     *
     * @param string field Optional field name.
     * @return mixed Result of the next row. If no field was specified,
     *               all fields are returned in a hash, otherwise the field's value
     *               is returned.
     */
    function get ($field = 0)
    {
        $row =  mysql_fetch_array ($this->res);
        return $field ? $row[$field] : $row;
    }

    /**
     * Get the number of rows affected by the insert operation. 
     *
     * @return int Returns 0 if operation was not an insert.
     */
    function num_rows () { return @mysql_num_rows ($this->res); }

    /**
     * Frees the result from memory.
     */
    function free () { mysql_free_result ($this->res); }
}
?>

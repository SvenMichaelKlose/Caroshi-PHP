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

    var $res;

    /**
     * Constructor for use of DBWrapper only.
     *
     * Calling this constructor is allowed for database wrappers only.
     *
     * @access private
     * @param mixed res Result set of internal type.
     */
    function &DB_result (&$res) { $this->res =& $res; }

    /**
     * Fetch first/next row from result set.
     *
     * @return array Columns of the next row. The columns names are used
     *               for the array's key names.
     */
    function &get () { return mysql_fetch_array ($this->res); }

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

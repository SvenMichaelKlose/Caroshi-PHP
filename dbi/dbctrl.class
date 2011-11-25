<?php
  # $Id: dbctrl.class,v 1.21 2002/06/08 20:02:38 sven Exp $
  #
  # Common database interface.
  #
  # Copyright (c) 2000-2002 dev/consulting GmbH
  #                         Sven Michael Klose <sven@devcon.net>
  #
  # This library is free software; you can redistribute it and/or
  # modify it under the terms of the GNU Lesser General Public
  # License as published by the Free Software Foundation; either
  # version 2.1 of the License, or (at your option) any later version.
  #
  # This library is distributed in the hope that it will be useful,
  # but WITHOUT ANY WARRANTY; without even the implied warranty of
  # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
  # Lesser General Public License for more details.
  #
  # You should have received a copy of the GNU Lesser General Public
  # License along with this library; if not, write to the Free Software
  # Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

  require_once 'dbi/dbwrapper.class';

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
    var $db_name;
    var $prefix;

    /**
     * Set up a database connection or reuse an existing one.
     *
     * @access public
     * @param string dbname Name of the database to connect to.
     * @param string host   Host which to connect.
     * @param string user   User name.
     * @param string passwd Password.
     */
    function &DBCtrl ($dbname, $host, $user, $passwd)
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
     * @return object db_result
     */
    function &select ($what, $table, $where = '', $tail = '')
    {
      $q = 'SELECT ' . $what . ' FROM ' . $this->prefix . $table;
      if ($where)
        $q .= ' WHERE ' . $where;
      if ($tail)
        $q .= ' ' . $tail;
      return $this->query ($q);
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
    function &update ($table, $set, $where)
    {
      $q = 'UPDATE ' . $this->prefix . $table . ' SET ' . $set;
      if (!$where)
        die ('dbctrl::update(): Need where clause. ' . $q);
      $q .= ' WHERE ' . $where;
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
    function &insert ($table, $set)
    {
      $set = " SET $set";
      return $this->query ('INSERT INTO ' . $this->prefix . $table .$set);
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
    function &delete ($table, $where = '')
    {
      $q = 'DELETE FROM ' . $this->prefix . $table;
      if ($where)
        $q .= ' WHERE ' . $where;
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

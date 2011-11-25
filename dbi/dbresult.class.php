<?php
  # $Id: dbresult.class.php,v 1.4 2002/05/31 19:21:53 sven Exp $
  #
  # Database-dependent query wrapper
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

<?php
  # $Id: dbconf.class,v 1.21 2002/06/01 05:06:34 sven Exp $
  #
  # Copyright (c) 2000-2001 dev/consulting GmbH
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
   * Read configuration from database.
   *
   * @access public
   * @package Database interfaces
   * @author Sven Klose <sven@devcon.net>
   */
  class dbconf {
    var $db;
    var $_res;	# Helper for create().

    /**
     * Construct object.
     *
     * @access public
     * @param object dbctrl $db Database connection.
     */
    function &dbconf (&$db)
    {
      global $application_id, $config_table;

      # Check application ID.
      if (!isset ($application_id) || !$application_id)
        die ('$application_id missing.');
      if (!isset ($config_table) || !$config_table)
        die ('$config_table missing.');

      $this->db =& $db;
    }

    /**
     * Check if an entry exists.
     *
     * @access public
     * @param string $name Entry name.
     * @returns bool True if entry exists.
     */
    function exists ($name)
    {
      global $application_id, $config_table;

      if (!isset ($application_id) || !$application_id)
        die ('DBConf::exists(): No $application_id.');
      $res =& $this->db->select (
        'COUNT(id)', $config_table,
	'id_application=' . $application_id .
	' AND name=\'' . addslashes ($name) . '\''
      );
      if (!$res || $res->num_rows () < 1)
        return 0;
      list ($num) = $res->get ();
      $res->free ();
      return $num;
    }

    function _get ($name)
    {
      global $application_id, $config_table;

      # Fetch flags and data from config record.
      $this->_res =& $this->db->select (
        'is_file,data', $config_table,
	'id_application=' . $application_id .
        ' AND name=\'' . addslashes ($name) . '\''
      );
      return $this->_res->get ();
    }

    /**
     * Fetch an entry's content.
     *
     * @access public
     * @param string $name Entry name.
     * @returns mixed Contents of entry. If the entry points to a file,
     *                the file content is returned.
     */
    function &get ($name)
    {
      list ($is_file, $data) = $this->_get ($name);

      # If it's a file name read the file in.
      if (!$is_file)
        return $data;

      if (!file_exists ($data)) {
        echo 'dbi/dbconf::get(): File "' . $data .
             '" for config entry "' . $name . '" not found.<br>';
        return;
      }

      if (!($fd = @fopen ($data, 'r')))
        return;

      $data = '';
      while ($tmp = fgets ($fd, 65535))
         $data .= $tmp;
      fclose ($fd);

      return $data;
    }

    /**
     * Check if an entry points to a file.
     *
     * @access public
     * @param string $name Entry name.
     * @returns bool True if entry exists.
     */
    function is_file ($name)
    {
      list ($is_file, $data) = $this->_get ($name);
      return $is_file;
    }

    /**
     * Write a config record.
     *
     * @access public
     * @param string $name Entry name.
     * @param mixed $data New content of entry.
     * @param boolean $is_file If true, $data contains the name of a file that
     *                      holds the content.
     */
    function set ($name, $data, $is_file = 0)
    {
      global $application_id, $config_table;

      $res =& $this->db->select (
        'COUNT(id)', $config_table, 'id_application=' . $application_id
      );
      list ($tmp) = $res->get ();
      $q = "data='" . addslashes ($data) . "',is_file=$is_file";
      $q2 = "id_application=$application_id";
      if ($tmp)
        $this->db->update ($config_table, $q, "$q2 AND name='$name'");
      else
        $this->db->insert ($config_table, $q, "$q2,name='$name'");
    }

    /**
     * Create a new config record.
     *
     * @access public
     * @param string $name Entry name.
     * @param string $descr Human-readable entry description.
     */
    function create ($name, $descr)
    {
      global $application_id, $config_table;

      $data = $this->get ($name);
      if ($this->_res->num_rows () > 0)
        return;

      $data = addslashes ($data);
      $descr = addslashes ($descr);
      $q = "data='$data',id_application=$application_id,name='$name'," .
           "descr='$descr'";
      $this->db->insert ($config_table, $q);
      $this->_res->free ();
    }

    /**
     * Define database tables.
     *
     * @access public
     * @param object dbdepend Database description to use.
     */
    function define_tables (&$def)
    {
      global $config_table;
      
      if (!isset ($config_table))
        $config_table = 'config';

      $def->define_table (
        $config_table,
	array (array ('n' => 'id',
                      't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY'),
	       array ('n' => 'id_application',
                      'i' => 'true',
                      't' => 'INT NOT NULL'),
	       array ('n' => 'is_file',
                      'i' => 'true',
                      't' => 'INT NOT NULL'),
	       array ('n' => 'mime',
                      't' => 'VARCHAR(255) NOT NULL'),
	       array ('n' => 'name',
                      'i' => 'true',
                      't' => 'VARCHAR(255) NOT NULL'),
	       array ('n' => 'descr',
                      't' => 'VARCHAR(255) NOT NULL'),
	       array ('n' => 'data',
                      't' => 'MEDIUMTEXT NOT NULL'))
      );
      # XXX Should be id.
      $def->set_primary ($config_table, 'name');
    }
  }
?>

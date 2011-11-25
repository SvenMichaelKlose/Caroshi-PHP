<?php
  # $Id: dbsession.class,v 1.52 2002/08/15 23:56:24 sven Exp $
  #
  # Databased session management
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
   * Databased session management.
   *
   * @access public
   * @package Database interfaces
   */
  class DBSESSION {

    /**
     * Set up a session manager.
     *
     * @access public
     * @param object dbctrl
     * @param integer $time_to_live Number of seconds a session must be unused
     *                          before it expires.
     */
    function &DBSESSION (&$db, $time_to_live = 36000)
    {
      $this->_db = &$db;
      $this->_ttl = $time_to_live;
      $this->_clear ();
    }

    /**
     * Define the SQL tables used for session storage.
     *
     * @access public
     */
    function define_tables ()
    {
      $def =& $this->_db->def;

      $def->define_table (
	$this->_table,
	array (array ('n' => 'id',
                      't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY'),
	       array ('n' => 'time',
                      'i' => true,
                      't' => 'INT NOT NULL'),
	       array ('n' => 'is_locked',
                      'i' => true,
                      't' => 'INT NOT NULL'),
	       array ('n' => 'skey',
                      'i' => true,
                      't' => 'VARCHAR(255) NOT NULL'),
	       array ('n' => 'data',
                      't' => 'MEDIUMTEXT NOT NULL'))
      );
      $def->set_primary ($this->_table, 'id');
    }

    /**
     * Read the current numeric session id.
     *
     * @access public
     * @public string $key Session key.
     * @returns string Nothing if key doesn't match any eisting session.
     */
    # If there's a session key, read the id from the session table
    function read_id ($key)
    {
      $db =& $this->_db;
      $table = $this->_table;
      $ttl = $this->_ttl;

      # Remove old sessions.
      if ($ttl)
        $db->delete ($table, 'time<' . (time () - $ttl));

      if (!$key)
        return;
      $this->_key = $key;
      $res =& $db->select ('id,is_locked,time,data', $table, "skey='$key'");
      if ($res->num_rows () < 1)
        return;

      $row =& $res->get ();
      if ($row['is_locked'] || $row['time'] < time () - $ttl) {
        $this->_key = '';
        return 0;
      }
      $db->update ($table, 'time=' . time (), "skey='$key'");
      $this->_data = unserialize ($row['data']);
      return $this->_id = $row['id'];
    }

    /**
     * Create a new session if none already exists.
     *
     * @access public
     */
    function force_key ()
    {
      $key = $this->_key;
      if ($this->_key)
	return;
      $table = $this->_table;
      $db =& $this->_db;

      while ($this->read_id ($key = uniqid (rand ()))); # Avoid double keys.
      $this->_key = $key;
      $q = "skey='$key',time=" . time ();
      $db->insert ($table, $q);
      $this->_id = $db->insert_id ();
    }

    /**
     * Lock current session forever.
     *
     * A locked session can't be used nor destroyed anymore.
     *
     * @access public
     */
    function lock ()
    {
      $id = $this->_id;
      if (!$id)
	die ('dbsession::lock(): No session.');

      $this->_db->update ($this->_table, 'is_locked=1', "id=$id");
    }

    /**
     * Destroy current session.
     *
     * The session is removed from the database forever.
     *
     * @access public
     */
    function destroy ()
    {
      $id = $this->_id;
      if (!$id)
	die ('dbsession::destroy(): No session.');

      $this->_db->delete ($this->_table, "id=$id");
      $this->_clear ();
    }

    /**
     * Get current session key.
     *
     *
     * @access public
     * @returns string Session key of random alphanumeric and numeric chars.
     */
    function key ()
    {
      return $this->_key;
    }

    /**
     * Get internal numeric session id.
     *
     * The numeric session id should not be used in public!
     *
     * @access public
     * @returns int Numeric session id.
     */
    function id ()
    {
      return $this->_id;
    }

    /**
     * Store data in a session entry.
     *
     * @access public
     * @param string $entry Name of the entry to set.
     * @param mixed $data Data to store.
     */
    function set ($entry, $data)
    {
      $this->_data[$entry] = $data;
      $this->_write ();
    }

    /**
     * Unset a session entry.
     *
     * @access public
     * @param string $entry Name of the entry to set.
     */
    function clear ($entry)
    {
      unset ($this->_data[$entry]);
      $this->_write ();
    }

    /**
     * Get a session entry.
     *
     * @access public
     * @param string $entry Name of the entry to set.
     */
    function get ($entry)
    {
      $e =& $this->_data[$entry];
      if (isset ($e))
        return $e;
    }

    /**
     * Set timeout for this manager.
     *
     * @access public
     * @param integer $seconds Number of seconds a session must be unused before
     *                     it expires.
     */
    function set_timeout ($seconds)
    {
      $this->_ttl = $seconds;
    }

    /**
     * Set name of sql table where sesuibs are stored.
     *
     * @access public
     * @param string $table Table name.
     */
    function set_table ($table)
    {
      if (!trim ($table))
        die ('dbsession::set_table(): Table name must not be empty.');
      if (!is_string ($table))
        die ('dbsession::set_table(): Table name must be a string.');

      $this->_table = $table;
    }

    /**
     * Initialize this manager.
     *
     * @access private
     */
    function _clear ()
    {
      $this->_key = '';
      $this->_id = 0;
      $this->_data = array ();
    }

    /**
     * Write out session data to database.
     *
     * @access private
     */
    function _write ()
    {
      $set = "data='" . addslashes (serialize ($this->_data)) . "'";
      $this->_db->update ($this->_table, $set, 'id=' . $this->_id);
    }

    var $_db;
    var $_key;
    var $_id;
    var $_ttl;
    var $_data;
    var $_ttl;
    var $_table = 'sessions';
  }
?>

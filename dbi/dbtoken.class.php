<?php
  # Copyright (c) 2000-2002 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


  require_once 'object/is_a.php';
  require_once 'object/singleton.class.php';

  /**
   * Default token time which is erased after timeout.
   * @const TOKEN_DEFAULT
   */
  define ('TOKEN_DEFAULT', 0);

  /**
   * Token that can be used axactly once.
   * @const TOKEN_ONETIME
   */
  define ('TOKEN_ONETIME', 1);

  /**
   * Token that is always reused for the same content.
   * @const TOKEN_REUSE
   */
  define ('TOKEN_REUSE', 2);

  /**
   * Databased token manager.
   *
   * Tokens are keys associated with a dbsession object and mixed data.
   *
   * @access public
   * @package Database interfaces
   */
  class dbtoken {

    function dbtoken ($db, $session)
    {
      if (!is_a ($db, 'dbctrl'))
        die ('token::token(): Need dbctrl object.');
      if (!is_a ($session, 'dbsession'))
        die ('token::token(): Need dbsession object.');

      $this->_db =& $db;
      $this->_session =& $session;
      $this->_ttl = 30 * 60; # 1h time to live.
      $this->_time = time ();
      $this->_uniqid = uniqid (rand ());
    }

    function set_timeout ($seconds)
    {
      if (!is_int ($seconds))
        die ("dbtoken::set_timeout(): Argument is not an integer (seconds).");
      if ($seconds < 1)
        die ("dbtoken::set_timeout(): Number of seconds must be >1.");

      $this->_ttl = $seconds;
    }

    # Read token data from database.
    function get ($token)
    {
      $this->_write_tokens (); # Be in sync.

      $res = $this->_db->select ('id,data', $this->_token_table, "name='$token'");
      if ($res->num_rows () < 1)
        return false;
      list ($this->_id_token, $data) = $res->get ();
      return unserialize ($data);
    }

    /**
     * Update existing token.
     *
     * @access public
     * @param string $name Token name.
     * @param mixed $data new content.
     */
    function write ($name, $data)
    {
      $this->_write_tokens (); # Be in sync.

      if (!$name)
        die ('dbtoken::write(): Need a name.');

      if (!isset ($this->_types[$name])) {
        $this->_tokens[$name] =& $data;
        $this->_types[$name] = TOKEN_DEFAULT;
        return;
      }

      $data = addslashes (serialize ($data));
      $this->_db->update ($this->_token_table, "data='$data'", "name='$name'");
    }

    /**
     * Create a token with data and return its name.
     *
     * @access public
     * @param mixed $data The data that is stored in the new token.
     * @param integer $type Token type (see constants section).
     */
    function create ($data, $type = TOKEN_DEFAULT)
    {
      $name = $this->_uniqid . $this->_num_tokens++;

      $is_onetime = $type & TOKEN_ONETIME ? 1 : 0;
      # Use already created link for permanent views.
      if ($type == TOKEN_REUSE) {
      	$q = "data='" . addslashes (serialize ($data)) . "'";
      	$res = $this->_db->select ('name', $this->_token_table, $q);
      	if ($res->num_rows () > 0)
          list ($name) = $res->get ();
        else
	  $this->_write_token ($name, $data, $is_onetime);
	return $name;
      }

      # Add new token entry.
      $this->_tokens[$name] =& $data;
      $this->_types[$name] =& $type;

      # Write tokens from time to time.
      if (!($this->_num_tokens % 1000))
        $this->_write_tokens ();

      return $name;
    }

    # Remove all tokens of this session.
    function clear_all ()
    {
      $table =& $this->_token_table;
      $id_session = $this->_session->id ();
      $this->_db->delete ($table, "id_session=$id_session");
    }

    function close ()
    {
      $this->_write_tokens ();
    }

    function set_table ($table_name)
    {
      if (!trim ($table_name))
        die ('dbtoken::define_table(): Table name must not be empty.');
      if (!is_string ($table_name))
        die ('dbtoken::define_table(): Table name must be a string.');
      $this->_token_table = $table_name;
    }

    function define_database (&$def)
    {
      if (!$this)
         die ('define_database(): Thisis not a static function.');

      $def->define_table (
        $this->_token_table,
        array (array ('n' => 'id',
                      't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY'),
               array ('n' => 'name',
                      'i' => true,
                      't' => 'VARCHAR (64) NOT NULL'),
               array ('n' => 'id_parent',
                      'i' => true,
                      't' => 'INT NOT NULL'),
               array ('n' => 'id_session',
                      'i' => true,
                      't' => 'INT NOT NULL'),
               array ('n' => 'is_onetime',
                      'i' => true,
                      't' => 'INT NOT NULL'),
               array ('n' => 't_creat',
                      'i' => true,
                      't' => 'INT NOT NULL'),
               array ('n' => 'data',
                      't' => 'MEDIUMTEXT NOT NULL'))
      );	
    }

    # Delete all one-time tokens that don't belong to this one.
    function _kill_old_tokens ()
    {
      $id_token = $this->_id_token;
      $id_session = $this->_session->id ();
      $table =& $this->_token_table;
      $ttl =& $this->_ttl;

      if ($id_token) {
        $q = "id_parent!=$id_token AND is_onetime=1 AND id_session=$id_session";
        $this->_db->delete ($table, $q);
      }
      $this->_db->delete ($table, 't_creat<' . (time () - $ttl));
    }

    function _write_token ($name, $data, $is_onetime)
    {
      $id_token = $this->_id_token;
      $id_session = $this->_session->id ();
      $table =& $this->_token_table;
      $time = $this->_time;
      $data = addslashes (serialize ($data));

      $q = "id_parent='$id_token',id_session=$id_session," .
           "is_onetime=$is_onetime,t_creat=$time,name='$name', data='$data'";
      $this->_db->insert ($table, $q);
    }
 
    function _write_tokens ()
    {
      $tokens =& $this->_tokens;
      if (!isset ($tokens))
        return;

      foreach ($tokens as $name => $data) {
        $is_onetime = $this->_types[$name] & TOKEN_ONETIME ? 1 : 0;
        $this->_write_token ($name, $data, $is_onetime);
      }
      unset ($this->_tokens);
    }

    var $_db;      # dbi.class instance.
    var $_session; # dbsession.class instance.
    var $_tokens;
    var $_num_tokens = 900;
    var $_id_token = 0;
    var $_uniqid;
    var $_token_table = 'tokens';
  }
?>

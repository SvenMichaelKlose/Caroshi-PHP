<?php
  /**
   * Event handler for MIME-headered output.
   *
   * @access public
   * @module mime
   * @package User interface
   */

  # Copyright (c) 2000-2002 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.

  /**
   * Initialise this module.
   *
   * @param object application $this
   */
  function _mime_init (&$this)
  {
    $this->add_function ('__return_mime', TOKEN_REUSE);
  }

  /**
   * Event handler: Output a file with MIME header.
   *
   * Event argument 'source' contains the source name. 'column' contains the
   * field name, 'primary' contains the primary key name, 'key' contains the
   + key of the record and 'type' contains the MIME type.
   *
   * @param object application $this
   */
  function __return_mime (&$this)
  { 
    $table = $this->arg ('source');
    $column = $this->arg ('column');
    $primary = $this->arg ('primary');
    $key = $this->arg ('key');
    $type = $this->arg ('type');

    $res =& $this->db->select ($column, $table, $primary . '=' . $key);
    $row =& $res->get ();
    
    $type = strtolower ($type);
    if ($type == 'image/jpg')
      $type = 'image/jpeg';

    Header ('Content-type: ' . $type);
    echo $row[$column];

    exit;
  }
?>

<?php
  /**
   * Event handler for MIME-headered output.
   *
   * @access public
   * @module mime
   * @package User interface
   */

  # $Id: mime.php,v 1.10 2002/06/08 20:20:49 sven Exp $
  #
  # MIME file export from database
  #
  # Copyright (c) 2000-2002 dev/consulting GmbH
  #		            Sven Michael Klose (sven@devcon.net)
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

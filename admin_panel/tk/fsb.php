<?php
  /**
   * File selector page.
   *
   * @access public
   * @module tk_fsb
   * @package User interface toolkits
   */

  # $Id: fsb.php,v 1.12 2002/06/01 01:33:18 sven Exp $
  #
  # Copyright (c) 2001 dev/consulting GmbH
  #                    Sven Michael Klose <sven@devcon.net>
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
   * Initialise the toolkit.
   *
   * @access public
   * @param object application $this
   */
  function tk_fsb_init (&$this)
  {
    $this->add_function ('tk_fsb');
  }

  /**
   * Event handler: Toolkit entry point.
   *
   * This function doesn't work.
   *
   * @access public
   * @param object application $this
   */
  function tk_fsb (&$this)
  {
    $data = $this->arg ('data', ARG_SUB);
    $ret = $this->arg ('ret', ARG_SUB);
    $filefunc = $this->arg ('filefunc', ARG_SUB);
    $dir = $this->arg ('dir', ARG_OPTIONAL);

    $ui =& admin_panel::instance ();

    if (!$dir)
      $dir = $GLOBALS['DOCUMENT_ROOT'];

    echo '[' . $ui->link ('zur&uuml;ck', 'return2caller') . ']';

    echo '<b>Current path: ' . $dir . '</b><hr>';
    $handle = opendir ($dir);
    $ui->open_table ();
    while (($file = readdir($handle)) !== false) {
      $dirfile = $dir . '/' . $file;
      $ft = filetype ($dirfile);
      $ui->open_row ();
      $a['dir'] = $dir . '/' . $file;
 
      switch ($ft) {
	case 'dir':
          $ui->link ($file, new_view ('tk_fsb', $a));
	  break;
	default:
          $ui->link ($file, $filefunc,
	             array ($ret => $dir . '/' . $file, 'data' => $data));
      }
      $ui->label ($ft);
      $ui->close_row ();
    }
    $ui->close_table ();
    closedir($handle); 
  }
?>

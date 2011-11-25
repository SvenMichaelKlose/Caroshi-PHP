<?php
  /**
   * Read htpasswd files.
   *
   * @access public
   * @module htpasswd
   * @package File functions
   */

  # $Id: htpasswd.php,v 1.3 2002/06/01 04:45:34 sven Exp $
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
   * Return array of accounts in htpasswd file.
   *
   * @access public
   * @param string $file Path to password file.
   * @returns array Crypted passwords keyed by user name.
   */
  function htpasswd_read ($file)
  {
    $lines = file ($file);
    foreach ($lines as $line) {
      $tmp = explode (':', trim ($line));
      $accounts[$tmp[0]] = $tmp[1];
    }
    return $accounts;
  }

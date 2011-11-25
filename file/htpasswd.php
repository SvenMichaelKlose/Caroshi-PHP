<?php
  /**
   * Read htpasswd files.
   *
   * @access public
   * @module htpasswd
   * @package File functions
   */

  # Copyright (c) 2001 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


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

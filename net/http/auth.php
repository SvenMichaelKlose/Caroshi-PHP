<?php
  /**
   * HTTP password authentication
   *
   * @access public
   * @module http_auth
   * @package Network functions
   */

  # $Id: auth.php,v 1.5 2002/06/23 16:09:46 sven Exp $
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
   * Send error 401.
   *
   * @access public
   * @param string $realm
   */
  function http_auth_logout ($realm = 'Caroshi')
  {
    header ('WWW-Authenticate: basic realm="' . $realm . '"');
    header ('HTTP/1.0 401 Unauthorized');
    echo '<h1>Permission denied.</h1>';
  }

  /**
   * Do HTTP authentication.
   *
   * This function stops program execution if authentication fails.
   *
   * @access public
   * @param array $accounts Crypted passwords keyed by user name.
   * @param string $realm
   * @see htpasswd_read()
   */
  function http_auth ($accounts, $realm = 'Caroshi')
  {
    global $PHP_AUTH_USER, $PHP_AUTH_PW;

    if (!$PHP_AUTH_PW || !isset ($PHP_AUTH_USER)
      || !isset ($accounts[$PHP_AUTH_USER])
      || ($PHP_AUTH_PW != '' && $accounts[$PHP_AUTH_USER] != ''
      && $accounts[$PHP_AUTH_USER] != crypt ($PHP_AUTH_PW, substr($accounts[$PHP_AUTH_USER], 0, CRYPT_SALT_LENGTH)))
    ) {
      http_auth_logout ($realm);
      exit;
    }
    return true;
  }
?>

<?php
/**
 * HTTP password authentication
 *
 * @access public
 * @module http_auth
 * @package Network functions
 */

# Copyright (c) 2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


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

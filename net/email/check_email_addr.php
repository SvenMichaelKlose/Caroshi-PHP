<?php
/**
 * Check if email address is a mail exchange point.
 *
 * @access public
 * @module check_email_addr
 * @package Network functions
 */

# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


/**
 * Check if the given domain is a known mail exchange point.
 *
 * @access public
 * @param string $adr Email address.
 * @returns bool True, if domain is a known mail exchange point.
 */
function check_email_addr ($adr)
{
    if (($at = strpos ($adr, '@')) < 0)
        return false;
    if (!checkdnsrr (substr ($adr, $at + 1), 'MX'))
        return false;
    return true;
}
?>

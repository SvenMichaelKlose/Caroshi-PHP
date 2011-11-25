<?php
  /**
   * Check if email address is a mail exchange point.
   *
   * @access public
   * @module check_email_addr
   * @package Network functions
   */

  # $Id: check_email_addr.php,v 1.3 2002/06/01 04:45:34 sven Exp $
  #
  # Copyright (c) 2000-2001 dev/consulting GmbH
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

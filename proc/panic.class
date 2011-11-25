<?php
  /**
   * Panicing and monitoriong,
   *
   * @access public
   * @module panic
   * @package Application server
   */

  # $Id: panic.class,v 1.4 2002/05/31 18:51:10 sven Exp $
  #
  # Panic report for various reasons. Can be used everywhere.
  #
  # Copyright (c) 2000-2001 dev/consulting GmbH
  #                         Sven Klose <sven@devcon.net>
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
   * Warn server admin and stop program execution.
   *
   * The email address is taken from environment variable SERVER_ADMIN.
   * This function never returns.
   *
   * @access public
   * @param string $reason Reason for panic.
   * @param string $message Full message for mail body.
   */
  function panic ($reason, $message = '')
  {
    global $SERVER_ADMIN, $HOSTNAME, $SERVER_NAME;

    $msg = $message . "\nGlobal variables:\n\n";

    foreach ($GLOBALS as $k => $v)
      $msg .= "$k = $v\n";

    if (!$reason)
      $reason = "Need support.";
    if (!isset ($SERVER_ADMIN))
      $SERVER_ADMIN = 'root';
    mail ($SERVER_ADMIN, "Panic: $HOSTNAME, $SERVER_NAME: $reason", $msg);
    exit;
  }
?>

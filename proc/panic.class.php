<?php
  /**
   * Panicing and monitoriong,
   *
   * @access public
   * @module panic
   * @package Application server
   */

  # Copyright (c) 2000-2001 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


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

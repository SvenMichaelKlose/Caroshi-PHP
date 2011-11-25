<?php
  # Copyright (c) 2000-2002 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


  /**
   * Context object
   *
   * @package User interface
   */
  class _admin_panel_view {
    /**
     * Context cursor.
     * @var object cursor
     */
    var $cursor;

    /**
     * True: Don't generate a form in next open_source.
     * @var boolean
     */
    var $no_update;

    /**
     * Optional default form function.
     * @var object event
     */
    var $defaultfunc;
  }
?>

<?php
  # Copyright (c) 2002 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


  /**
   * Subsession object
   *
   * @access private
   * @package Application server
   */
  class _application_subsession {
    var $args;    # Session arguments.
    var $parent;  # Parent view object..

    function _application_subsession ($parent = 0)
    {
      $this->parent = $parent;
    }
  }
?>

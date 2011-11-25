<?php
  /**
   * Convert German ligatures to 7-bit ASCII characters.
   *
   * @access public
   * @module germanlig2ascii
   * @package String functions
   */

  # Copyright (c) 2000-2001 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


  /**
   * Convert German ligatures to 7-bit ASCII characters.
   *
   * @access public
   * @param string $txt
   * @returns string
   */
  function germanlig2ascii ($txt)
  {
    # XXX Yurgh...there is a nice built-in php function for such things I think.
    $txt = ereg_replace ("ä", "ae", $txt);
    $txt = ereg_replace ("ö", "oe", $txt);
    $txt = ereg_replace ("ü", "ue", $txt);
    $txt = ereg_replace ("ß", "ss", $txt);
    $txt = ereg_replace ("Ä", "Ae", $txt);
    $txt = ereg_replace ("Ö", "Öe", $txt);
    $txt = ereg_replace ("Ü", "Üe", $txt);
    return $txt;
  }

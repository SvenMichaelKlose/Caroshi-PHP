<?php
  /*
   * Convert German HTML ligatures to latin.
   *
   * @access public
   * @module htmllig2latin
   * @package String functions
   */

  # Copyright (c) 2000-2001 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


  $__HTMLLIG2LATIN_TABLE = get_html_translation_table (HTML_ENTITIES);
  $__HTMLLIG2LATIN_TABLE = array_flip ($__HTMLLIG2LATIN_TABLE);

  /*
   * Convert German HTML ligatures to html entities.
   *
   * @access public
   * @param string $txt
   * @returns string
   */
  function htmllig2latin ($txt)
  {
    return strtr ($txt, $GLOBALS['__HTMLLIG2LATIN_TABLE']);
  }
?>

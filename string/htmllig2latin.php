<?php
  /*
   * Convert German HTML ligatures to latin.
   *
   * @access public
   * @module htmllig2latin
   * @package String functions
   */

  # $Id: htmllig2latin.php,v 1.6 2002/06/08 19:59:10 sven Exp $
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

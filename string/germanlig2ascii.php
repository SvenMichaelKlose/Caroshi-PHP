<?php
  /**
   * Convert German ligatures to 7-bit ASCII characters.
   *
   * @access public
   * @module germanlig2ascii
   * @package String functions
   */

  # $Id: germanlig2ascii.php,v 1.3 2002/06/01 04:45:34 sven Exp $
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

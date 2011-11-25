<?php
  /**
   * Insert data into a template easily.
   *
   * @access public
   * @module quick_template
   * @package Text functions
   */

  # $Id: quick_template.php,v 1.3 2002/06/01 04:45:34 sven Exp $
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
   * Replace tags by data.
   *
   * Tags have the form <tagname>. If you want to replace all "<foo>" in a
   * template by "bar" you need $tags = array ('foo' => 'bar').
   *
   * @access public
   * @param array $tags Data keyed by tag name.
   * @param string $template
   * @returns string 
   */
  function &quick_template ($tags, $template)
  {
    if (!is_array ($tags))
      return $template;

    foreach ($tags as $k => $v)
      $template = preg_replace ("/<$k>/", $v, $template);
    return $template;
  }
?>

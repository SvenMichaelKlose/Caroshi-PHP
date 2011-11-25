<?php
  /**
   * is_a() implementation in php.
   *
   * @access public
   * @module is_a
   * @package Object functions and base classes.
   */

  # $Id: is_a.php,v 1.4 2002/05/31 19:21:53 sven Exp $
  #
  # (c) 2002 dev/consulting GmbH,
  #          Sven Klose <sven@devcon.net>
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

# Only include this function if it's not already defined.
if (!function_exists ('is_a')) {

  # Return true if $object is of class $class or one if its subclasses.
  function is_a ($object, $class)
  {
    if (!is_object ($object))
      return;
    if (!is_string ($class))
      die ('is_a(): Class name is not a string.');

    $class = strtolower ($class);
    if (get_class ($object) == $class)
      return true;

    return is_subclass_of ($object, $class);
  }

}
?>

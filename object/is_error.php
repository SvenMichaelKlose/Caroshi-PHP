<?php
  /**
   * is_error()
   *
   * Check if variable contains an error object.
   *
   * @access public
   * @module is_error
   * @package Object functions and base classes.
   */

  # $Id: is_error.php,v 1.2 2002/06/09 00:20:55 sven Exp $
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

  require_once 'object/error.class.php';

  /**
   * Check if variable contains an error object.
   *
   * @access public
   * @param mixed $var Variable to check,
   * returns bool
   */
  function is_error (&$var)
  {
    return is_a ($var, 'error');
  }
?>

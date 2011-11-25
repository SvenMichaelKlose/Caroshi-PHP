<?php
  /**
   * Various useful functions.
   *
   * @access public
   * @module util
   * @package User interface
   */

  # $Id: util.php,v 1.3 2002/06/25 01:03:24 sven Exp $
  #
  # SSI-independent built-in views for creating and deleting records.
  #
  # Copyright (c) 2000-2002 dev/consulting GmbH
  #                         Sven Michael Klose (sven@devcon.net)
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
 * Add handler functions to an application.
 *
 * @access public
 * @param object application $this
 * @param array $handlers List of handler names.
 */
function util_add_functions (&$this, &$handlers)
{
  if (!is_array ($handlers))
    die ('util_add_functions(): Handler list is not an array.');

  foreach ($handlers as $n) {
    $this->add_function ($n);
    $this->raw_views[$n] = true;
  }
}

/**
 * Add 'raw' handler functions to an application.
 *
 * No HTML header is generated for 'raw' handlers.
 *
 * @access public
 * @param object application $this
 * @param array $handlers List of handler names.
 */
function util_add_raw_functions (&$this, &$handlers)
{
  if (!is_array ($handlers))
    die ('util_add_raw_functions(): Handler list is not an array.');

  foreach ($handlers as $n) {
    $this->add_function ($n);
    $this->raw_views[$n] = true;
  }
}

/**
 * Initialise modules.
 *
 * The init function for each module must start with the module name
 * and end with '_init' (e.g. 'mymodule_init' for module 'mymodule').
 * Each init function takes a reference to the application object.
 *
 * @access public
 * @param object application $this
 * @param array $modules List of module names.
 */
function util_init_modules (&$this, &$modules)
{
  if (!is_array ($modules))
    die ('util_add_modules(): Module list is not an array.');

  foreach ($modules as $m) {
    $m .= '_init';
    $m ($this);
  }
}
?>

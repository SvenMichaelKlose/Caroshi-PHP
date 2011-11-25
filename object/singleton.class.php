<?php
  # $Id: singleton.class.php,v 1.6 2002/06/25 01:03:24 sven Exp $
  #
  # Singleton base class
  #
  # Copyright (c) 2002 dev/consulting GmbH, Sven Klose (sven@devcon.net)
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

  $_SINGLETONS = array ();

  /**
   * Base class for singletons.
   *
   * @access public
   * @package Object functions and base classes.
   */
  class singleton {

    function singleton (&$instance)
    {
      if (!is_object ($instance))
        die ('singleton constructor: Argument is not an object.');

      global $__SINGLETONS;
      $class = get_class ($instance);
      $i =& $__SINGLETONS[$class];

      if (isset ($i))
        die ("$class is a singleton class - can't construct twice.");
      $__SINGLETONS[$class] =& $instance;
    }

    function &instance ($class)
    {
      global $__SINGLETONS;
      $i =& $__SINGLETONS[$class];

      if (isset ($i));
        return $i;
    }
  }
?>

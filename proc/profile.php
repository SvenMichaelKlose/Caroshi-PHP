<?php
  /**
   * Profiling functions
   *
   * @access public
   * @module profile
   * @package Application server
   */

  # $Id: profile.php,v 1.3 2002/05/31 18:51:10 sven Exp $
  #
  # Copyright (c) 2002 dev/consulting GmbH
  #                    Sven Michael Klose <sven@devcon.net>
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
   * Start timer.
   *
   * @access public
   * @param string $name Name of timer.
   */
  function profile_start ($name)
  {
    global $_prof, $_prof_times;

    if (!isset ($_prof[$name])) {
      $_prof[$name] = array ();
      $_prof_times[$name] = 0;
    }
    array_push ($_prof[$name], gettimeofday ());
  }
 
  /**
   * Get time from timer.
   *
   * @access public
   * @param string $name Name of timer.
   * @returns float Time passed since timer start in seconds.
   */
  function profile_end ($name)
  {
    global $_prof, $_prof_times;

    $t = gettimeofday ();
    $t_start = array_pop ($_prof[$name]);
    $_prof_times[$name] +=
      (($t['usec'] + $t['sec'] * 1000000) -
      ($t_start['usec'] + $t_start['sec'] * 1000000)) / 1000000;
  }
?>

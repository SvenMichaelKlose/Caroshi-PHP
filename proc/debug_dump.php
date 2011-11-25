<?php
  /**
   * Debug dump function
   *
   * @access public
   * @module debug_dump
   * @package Application server
   */

  # $Id: debug_dump.php,v 1.9 2002/08/07 11:59:23 sven Exp $
  #
  # Nicely formatted debug dump.
  #
  # Copyright (C) 2001-2002 dev/consulting GmbH
  #                         Sascha Krause <sk@devcon.net>
  #                         Sven Klose <sven@devcon.net>
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

  # Debug levels.
  define ('DEBUG_DB', 1);
  define ('DEBUG_VIEWS', 2);

  /**
   * Dump a variable to HTML.
   *
   * @access public
   */
  function debug_dump ($a)
  {
    if (is_array ($a)) {
      ksort ($a);
      echo '<table border="1" cellpadding="0" cellspacing="0">';
      foreach ($a as $k => $v) {
        echo "<tr><td bgcolor=\"white\">$k&nbsp;</td><td bgcolor=\"silver\">";
        if (is_array ($v))
          debug_dump ($v);
        else {
	  if (is_bool ($v))
	    echo '<i>' . ($v ? 'true' : 'false') . '</i>';
          else if (is_string ($v))
	    echo '&quot;' . htmlentities (addslashes ($v)) . '&quot;';
          else if (is_object ($v)) {
            echo "<pre>";
            print_r ($v);
            echo "<pre>";
          } else
            echo "$v&nbsp;";
        }
        echo '</td></tr>';
      }
      echo '</table>';
    } else if (is_null ($a)) {
      echo '<table border="1" cellpadding="0" cellspacing="0">' .
           '<tr><td bgcolor="silver"><i>NULL</i></td></tr></table>';
    } else {
      echo '<table border="1" cellpadding="0" cellspacing="0">' .
           '<tr><td bgcolor="silver">';
      $tmp = array ($a);
      debug_dump ($tmp);
      echo '&nbsp;</td></tr></table>';
    }

    flush ();
  }

  /**
   * Dump environment variables.
   *
   * Only variables HTTP_SERVER_VARS, HTTP_ENV_VARS and HTTP_POST_VARS are
   * dumped.
   *
   * @access public
   */
  function debug_env_dump ()
  {
    $tmp = array ('HTTP_SERVER_VARS' => $GLOBALS['HTTP_SERVER_VARS'],
                  'HTTP_ENV_VARS' => $GLOBALS['HTTP_ENV_VARS'],
                  'HTTP_POST_VARS' => $GLOBALS['HTTP_POST_VARS']);
    debug_dump ($tmp);
  }
?>

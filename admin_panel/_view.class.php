<?php
  # $Id: _view.class.php,v 1.5 2002/06/01 05:06:34 sven Exp $
  #
  # Web based user interface
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
   * Context object
   *
   * @package User interface
   */
  class _admin_panel_view {
    /**
     * Context cursor.
     * @var object cursor
     */
    var $cursor;

    /**
     * True: Don't generate a form in next open_source.
     * @var boolean
     */
    var $no_update;

    /**
     * Optional default form function.
     * @var object event
     */
    var $defaultfunc;
  }
?>

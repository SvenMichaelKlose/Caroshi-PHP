<?php
  # $Id: error.class.php,v 1.1 2002/06/09 00:15:26 sven Exp $
  #
  # Error object
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

  /**
   * Error class for return values.
   *
   * @access public
   * @package Object functions and base classes.
   */
  class error {

    /**
     * Error message.
     *
     * @access private
     * @var string
     */
    var $_message;

    /**
     * Error mode.
     *
     * @access private
     * @var int PHP error mode
     */
    var $_mode;

    /**
     * Create error object.
     *
     * @access public
     * @oaram string $message Error message.
     * @oaram string $mode PHP error mode
     */
    function error ($message, $mode)
    {
      $this->_message = $message;
      $this->_mode = $mode;
    }

    /**
     * Get error message.
     *
     * @access public
     * @returns string
     */
    function message ()
    {
      return $this->_message;
    }

    /**
     * Get error mode.
     *
     * @access public
     * @returns int Error mode.
     */
    function mode ()
    {
      return $this->_mode;
    }
  }
?>

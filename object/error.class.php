<?php
# Copyright (c) 2002 dev/consulting GmbH, Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


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

<?php
  # $Id: _form_element.class.php,v 1.6 2002/06/17 00:52:41 sven Exp $
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
   * Form element object
   *
   * @package User interface
   */
  class _form_element {

    /**
     * Posted value.
     * @access public
     * @var mixed
     */
    var $val;

    /**
     * Context cursor.
     * @access public
     * @var object cursor
     */
    var $cursor = 0;

    /**
     * Set to 'true' if value is an uploaded file.
     * @var boolean
     */
    var $is_file = false;

    /**
     * Path to temporary file copy. 
     * @var string
     */
    var $file;

    /**
     * Token of form element that contains the original file name.
     * @var string
     */
    var $filenamefield;

    /**
     * Token of form element containing the file's MIME type.
     * @var string
     */
    var $typefield;

    /**
     * Index of the form this element belongs to.
     * @var integer
     */
    var $form_idx;

    /**
     * Set to 'true' if $view contains an event for a form handler.
     * @var boolean
     */
    var $is_submit = false;

    /**
     * Default event for the ekement's form which is used if there's no
     * element containing an event.
     * @var object event
     */
    var $defaultfunc = 0;

    /**
     * Name of form filter.
     * @var string
     */
    var $use_filter = 0;

    /**
     * Name of element filter for writes.
     * @var string
     */
    var $element_filter_write = '';

    /**
     * Event to trigger if this element was posted.
     * @var object event
     */
    var $view = 0;
  }
?>

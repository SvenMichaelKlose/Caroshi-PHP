<?php
  # Copyright (c) 2000-2002 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


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

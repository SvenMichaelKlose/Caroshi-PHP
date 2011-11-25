<?php
  # Default tag handlers for the template toolkit,
  #
  # Copyright (c) 2002 dev/consulting GmbH,
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


  # Return the value of the current result set.
  function &dirtag_cms_value (&$this, $args)
  {
    @$field = $args['match'];
    if (isset ($this->_results[$field]))
      return $this->_results[$field];
  }

  # List a result set.
  function &dirtag_cms_list (&$this, $args)
  {
    $scanner =& $this->get_scanner ();
    @$field = $args['match'];
    @$result_set = $this->_results[$field];
    if (!$result_set)
      return;
    $out = '';

    # Save current result set.
    array_push ($this->_result_stack, $this->_results);

    # Merge each result with result set and execute inner block.
    foreach ($result_set as $result) {
      $tmp = $this->_results;
      $this->_results = array_merge ($this->_results, $result);
      $out .= $scanner->exec ($args['_']);
      $this->_results = $tmp;
    }

    # Restore former result set.
    $this->_results = array_pop ($this->_result_stack);

    return $out;
  }

  # Include block if value exists.
  function &dirtag_cms_if (&$this, $args)
  {
    $scanner =& $this->get_scanner ();
    @$field = $args['match'];

    if (isset ($this->_results[$field]))
      return $scanner->exec ($args['_']);
  }

  # Include block if value doesn't exist.
  function &dirtag_cms_if_not (&$this, $args)
  {
    $scanner =& $this->get_scanner ();
    @$field = $args['match'];

    if (!isset ($this->_results[$field]))
      return $scanner->exec ($args['_']);
  }
?>

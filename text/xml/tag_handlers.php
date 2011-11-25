<?php
  # $Id: tag_handlers.php,v 1.3 2002/05/23 23:49:44 sven Exp $
  #
  # Default tag handlers for the template toolkit,
  #
  # Copyright (c) 2002 dev/consulting GmbH,
  #                    Sven Klose <sven@devcon.net>
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

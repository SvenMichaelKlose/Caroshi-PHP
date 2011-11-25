<?php
  /**
   * Insert data into a template easily.
   *
   * @access public
   * @module quick_template
   * @package Text functions
   */

  # Copyright (c) 2000-2001 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


  /**
   * Replace tags by data.
   *
   * Tags have the form <tagname>. If you want to replace all "<foo>" in a
   * template by "bar" you need $tags = array ('foo' => 'bar').
   *
   * @access public
   * @param array $tags Data keyed by tag name.
   * @param string $template
   * @returns string 
   */
  function &quick_template ($tags, $template)
  {
    if (!is_array ($tags))
      return $template;

    foreach ($tags as $k => $v)
      $template = preg_replace ("/<$k>/", $v, $template);
    return $template;
  }
?>

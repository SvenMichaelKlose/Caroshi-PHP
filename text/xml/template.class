<?php
  # $Id: template.class,v 1.5 2002/06/01 04:45:34 sven Exp $
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

  include 'text/xml/tag_handlers.php';
  include 'text/xml/scanner.class';

  # Default name space.
  define ('XML_TEMPLATE_NS', 'CMS');

  # Default tags.
  define ('XML_TEMPLATE_TAGS', 'IF IF-NOT LINK LIST NAME TEMPLATE VALUE');

  /**
   * Template scanner
   *
   * @access public
   * @package Text functions
   */
  class xml_template {

    var $_scanner = 0;  # Reference to scanner instance.
    var $_results;      # Results for current template.
    var $_result_stack = array (); # Stacked results for nested blocks.

    /**
     * Initialise the module.
     *
     * @access public
     */
    function xml_template ()
    {
      # Create scanner instance.
      if (!$this->_scanner) {
        $this->_scanner =& new XML_SCANNER;
	$this->_scanner->set_ref ($this);
      }

      # Define standard tags.
      $this->_scanner->dirtag (XML_TEMPLATE_NS, XML_TEMPLATE_TAGS);
    }

    /**
     * Scan and process template.
     *
     * @access public
     * @param string $template
     * @param array $results Data to use with tag handlers.
     * @returns string
     */
    function exec ($template, &$results)
    {
      # Fetch document tree from database or scan template.
      $tree =& $this->fetch_tree ($template);

      # Store current result set.
      $this->_results =& $results;

      # Execute and output template.
      return $this->_scanner->exec ($tree);
    }

    /**
     * Get reference to scanner instance for use with extensions.
     *
     * @access public
     * @returns object xml_scanner
     */
    function &get_scanner ()
    {
      return $this->_scanner;
    }

    /**
     * Create document tree from template.
     *
     * @access private
     * @param string $template Template file name
     * @returns array Document tree
     */
    function &fetch_tree ($template)
    {
      if (!file_exists ($template)) {
        # realpath() on non-existing files returns an empty string.
        if (substr ($template, 0, 1) != '/')
           $template = getcwd() . '/' . $template;
        $template = realpath (dirname ($template)) . $template;
        die ("xml_scanner::fetch_tree(): File '$template' doesn't exist.");
      }

      # Read in template file.
      $fp = fopen ($template, 'r');
      $doc = fread ($fp, filesize ($template));
      fclose ($fp);

      # Convert template to intermediate format (the document tree) and
      # return it.
      $tree =& $this->_scanner->scan ($doc);
      return $tree;
    }
  }
?>

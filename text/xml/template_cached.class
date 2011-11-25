<?php
  # $Id: template_cached.class,v 1.5 2002/06/01 13:00:20 sven Exp $
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

  include 'text/xml/template.class';

  # Default table.
  define ('XML_TEMPLATE_CACHED_TBL', 'template_cache');

  /**
   * Template wizard based on lib/xml_scanner.class.
   *
   * Saves first scanning pass for a template by storing the document trees in
   * a SQL table. Use this like xml_template.
   *
   * @access public
   * @package Text functions
   */
  class xml_template_cached extends xml_template {

    var $_scanner = 0;  # Reference to scanner instance.
    var $_results;      # Results for current template.
    var $_result_stack = array (); # Stacked results for nested blocks.

    /**
     * Initialise the object.
     *
     * @access public
     * @param object dbctrl $db
     */
    function xml_template_cached ($db)
    {
      xml_template::xml_template ();
      $this->_db =& $db;
    }

    /**
     * Set up dbdepend description for cache table.
     *
     * @access public
     * @param object dbdepend $def
     */
    function define_table (&$def)
    {
      $def->define_table (
        XML_TEMPLATE_CACHED_TBL,
	array (array ('n' => 'name',
	              't' => 'VARCHAR(255) NOT NULL PRIMARY KEY'),
               array ('n' => 'data',
	              't' => 'MEDIUMTEXT NOT NULL'))
      );
    }

    /**
     * Create document tree from template.
     *
     * @access public
     * @param string $template
     * @returns array Document tree.
     */
    function &fetch_tree ($template)
    {
      $db =& $this->_db;

      $res =& $db->select ('data', XML_TEMPLATE_CACHED_TBL, "name='$template'");
      if ($res->num_rows () > 0) {
        list ($data) = $res->get ();
	return unserialize ($data);
      }

      $tree =& xml_template::fetch_tree ($template);
      $data = serialize ($tree);
      $db->insert (XML_TEMPLATE_CACHED_TBL, "data='$data',name='$template'");

      return $tree;
    }
  }
?>

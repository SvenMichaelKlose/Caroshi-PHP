<?php
  # $Id: dbisearch.php,v 1.16 2002/06/13 16:35:13 sven Exp $
  #
  # Generic editor for records in a single table.
  #
  # Needs $this->db->dep; (dbi/dbdepend.class)
  #
  # Copyright (c) 2001-2202 dev/consulting GmbH
  #                         Sven Klose <sven@devcon.net>
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
   * List result from tk/dbisearch.
   *
   * @access public
   * @param object application $this
   * @param array $config Configuration for tk_autoform_list_cursor().
   * @see tk_autoform_list_cursor()
   */
  function tk_autoform_list_search_results (&$this, $config = '')
  {
    $p =& admin_panel::instance ();
    $status = tk_dbisearch_has_result ($this);

    # A view is already set up by form_search().
    if ($status == TK_DBISEARCH_FOUND) {
      $cursor =& tk_dbisearch_get_results ($this);
      $p->open_table ();
      tk_autoform_list_cursor ($this, $cursor, $config);
      $p->close_table ();
    }
    return $status;
  }
?>

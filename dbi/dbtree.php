<?php
  /**
   * Stepping through SQL trees (deprecated).
   *
   * @access public
   * @module dbtree
   * @package Database interfaces
   */

  # $Id: dbtree.php,v 1.7 2002/05/31 19:21:53 sven Exp $
  #
  # Tree walking functions
  #
  # Copyright (c) 2000-2001 dev/consulting GmbH
  #                         Sven Michael Klose <sven@devcon.net>
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

  $_DBITREE_CACHE_PARENT = array ();

  # Get table name and primary key of a parent record.
  function dbitree_get_parent (&$db, &$table, &$id)
  {
    global $_DBITREE_CACHE_PARENT;

    $def =& $db->def;

    if (isset ($_DBITREE_CACHE_PARENT[$table][$id])) {
      list ($table, $id) = $_DBITREE_CACHE_PARENT[$table][$id];
      return;
    }

    if ($xref = $def->xref_table ($table)) {
      $res =& $db->select ('id_parent', $xref, 'id_child=' . $id);
      $ntable = $table;
    } else {
      $res =& $db->select ($def->ref_id ($table), $table, 'id=' . $id);
      $ntable = $def->ref_table ($table);
    }

    if ($res->num_rows () < 1) {
      $table = $id = 0;
      return;
    }

    list ($nid) = $res->get ();
    $_DBITREE_CACHE_PARENT[$table][$id] = array ($ntable, $nid);
    $table = $ntable;
    $id = $nid;
  }

  # Return DB_result of records that reference a table.
  #
  # $db:		A database connection.
  # $table/$id:	The table that is referenced.
  # $subtype:		The table name of records.
  #			If emptry, it's set to $table.
  function &dbitree_get_childs (&$db, $table, $id, $subtype = '')
  {
    $def =& $db->def;

    if (!$xref = $db->def->xref_table ($table)) {
      if (!$subtype)
        $subtype = $table;
      return $db->select ('*', $subtype, $def->ref_id ($subtype) . '=' . $id);
    }

    $res =& $db->select ('id_child', $xref, "id_parent=$id");
    $q = '';
    while (list ($id) = $res->get ()) {
      if ($q)
        $q .= ' OR ';
      $q .= "id=$id";
    }
    if ($subtype)
      $q = "id_type=$subtype AND ($q)";
    return $db->select ('*', $table, $q);
  }
?>

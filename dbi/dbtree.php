<?php
  /**
   * Stepping through SQL trees (deprecated).
   *
   * @access public
   * @module dbtree
   * @package Database interfaces
   */

  # Copyright (c) 2000-2001 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


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

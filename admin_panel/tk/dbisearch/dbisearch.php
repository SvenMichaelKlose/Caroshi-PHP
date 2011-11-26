<?php
/**
 * Database search for forms via cursor_sql
 *
 * @access public
 * @module tk_dbisearch
 * @package User interface toolkits
 * @see cursor_sql
 */

# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

require_once PATH_TO_CAROSHI . '/cursor/merged.class.php';

define ('TK_DBISEARCH_NOT_SEARCHED', 0);
define ('TK_DBISEARCH_NOT_FOUND', 1);
define ('TK_DBISEARCH_FOUND', 2);

/**
 * Search description object.
 *
 * @access public
 */
class tk_dbisearch_query {
    var $not_all = '';
    var $empty_fields = 'skip';
    var $base_exp = 'AND';
    var $where = '';
    var $fields = array ();
    var $size = 0;
    var $limit = 0;
    var $offset = 0;
    var $order = '';
    var $_sql_queries = array ();
}

/**
 * Initialise the toolkit.
 *
 * @access public
 * @param object application $app
 */
function tk_dbisearch_init (&$app)
{
    $app->add_function ('tk_dbisearch');
    $app->raw_views['tk_dbisearch'] = true;
}

/**
 * Full-text search event handler.
 *
 * Event argument 'query' must contain a tk_dbisearch_query object.
 *
 * @access public
 * @param object application $app
 */
function tk_dbisearch (&$app)
{
    $o = $app->arg ('query');

    $limit =& $o->limit;
    $offset =& $o->offset;
    if ($offset && !$limit)
        $limit = 10;
    else if (!$limit)
        $limit = 65535;

    _tk_dbisearch_query_sql_cursors ($app, $o);

    # Save query object for tk_dbisearch_get_query_object().
    $app->tk_dbisearch->query =& $o;
}

/**
 * Create queried cursor_sql objects.
 *
 * The cursors are stored in array $app->tk_dbisearch->cursors.
 *
 * @access private
 * @param object application $app
 * @param object tk_dbisearch $qo
 */
function _tk_dbisearch_query_sql_cursors (&$app, &$qo)
{
    $limit = (int) $qo->limit;
    $not_all = $qo->not_all;
    $offset = (int) $qo->offset;
    $where = $qo->where;
    $q =& $qo->_sql_queries;

    _tk_dbisearch_create_sql_queries ($app, $qo);

    $qo->size = 0;
    $res = array ();
    foreach ($q as $source => $query) {
        if ($where && $query)
            $query = "($query) AND $where";
        else {
            if (!$query && $not_all)
                continue;
            if ($where)
                $query = $where;
        }

        $l = $limit ? "LIMIT $offset,$limit" : '';
 
        # Query complete number of records.
        $cursor =& new cursor_sql ();
        $cursor->set_source ($source);
        $cursor->query ($query);
        $s = $cursor->size ();
        $qo->size += $s;
        if ($limit < 1)
            continue;

        # Step over cursor if offset is beyond number of results.
        if ($offset >= $s) {
            $offset -= $s;
            if ($offset < 0)
                $offset = 0;
            continue;
        }

        # Perform limited query.
        $cursor->query ($query, $l);

        $s = $cursor->size ();
        if ($s) {
            $offset = 0;
            $limit -= $s;
            $app->tk_dbisearch->cursors[] = $cursor;
        }
    }
}

/**
 * Create queries for all form fields if not already done.
 *
 * @access private
 * @param object application $app
 * @param object tk_dbisearch $qo
 */
function _tk_dbisearch_create_sql_queries (&$app, &$query)
{
    $fields =& $query->fields;
    if ($query->_sql_queries)
        return;

    foreach (array_keys ($fields) as $field)
        _tk_dbisearch_create_query ($app, $query, $field);
}

/**
 * Get element from field desribtion or from member variable in query object.
 *
 * tk_dbisearch_query->fields should contain superclasses of the object.
 *
 * @access private
 * @param object application $app
 * @param object tk_dbisearch $qo
 */
function &_tk_dbisearch_get_field (&$query, $field, $element)
{
    $q =& $query->fields[$field][$element];

    if ($q)
        return $q;
    return $query->$element;
}

/**
 * Create an SQL query for a single form field.
 *
 * The query is stored $query->_sql_queries.
 *
 * @access private
 * @param object application $app
 * @param object tk_dbisearch $query
 * @param string $field Form field name.
 */
function _tk_dbisearch_create_query (&$app, &$query, $field)
{
    $def =& $app->db->def;
    $empty_fields = _tk_dbisearch_get_field ($query, $field, 'empty_fields');
    $empty_fields = strtolower ($empty_fields);
    $base_exp = _tk_dbisearch_get_field ($query, $field, 'base_exp');
    $base_exp = strtoupper ($base_exp);
    $sources = $query->fields[$field]['sources'];
    $order  = $query->order;
    $q =& $query->_sql_queries;

    if (isset ($fields) && !is_array ($fields))
        die ("tk_dbisearch: Field '$field': Argument 'fields' is not an array.");
    if ($empty_fields && $empty_fields != 'skip' && $empty_fields != 'match')
        die ("tk_dbisearch: Unknown value '$empty_fields' for empty_fields.");
    if (!isset ($app->named_elements[$field]))
        die ("tk_dbisearch: Unknown form field '$field'.");

    $val = $app->named_elements[$field];
    $val = trim ($val);
    $val = addslashes ($val);

    foreach ($sources as $source => $fieldnames) {
        if (!isset ($q[$source]) && $empty_fields != 'skip')
            $q[$source] = '';

      # Skip empty field matches if so desired.
      if (!$val && $empty_fields == 'skip') {
          if (!isset ($q[$source]))
              $q[$source] = '';
          continue;
      }

      # Search over all fields if none specified by user.
      if (!sizeof ($fieldnames))
          $fieldnames = array_keys ($def->types ($source));

      if (!is_array ($fieldnames))
          die ("tk_dbisearch(): Field list ist not an array for source '$source', field '$field'.");

      # Assemble query for source.
      $qt = '';
      foreach ($fieldnames as $fieldname) {
          if ($qt)
              $qt .= ' OR ';
          if ($val)
              $qt .= "$fieldname LIKE '%$val%'";
          else
              $qt .= "$fieldname=''";
      }

      # Combine multiple queries in same source.
      if (!isset ($q[$source]))
          $q[$source] = '';
      $q[$source] .= $q[$source] ? " $base_exp " : '';
      $q[$source] .= "($qt)";

      # Append ORDER clause.
      if ($order)
          $q[$source] .= " ORDER BY $order";
    }
    $tmp =& $q;
}

/**
 * Get cursor with results.
 *
 * @access public
 * @param object application $app
 * @returns object cursor_merged
 */
function tk_dbisearch_get_results (&$app)
{
    $p =& admin_panel::instance ();

    $r = tk_dbisearch_has_result ($app);
    if ($r != TK_DBISEARCH_FOUND)
        return;

    # Record cache should not override our results.
    $p->clear_record_cache ();

    $res =& new cursor_merged ();
    $res->query ($app->tk_dbisearch->cursors);
    return $res;
}

/**
 * Check if a search was performed or not and if records were found.
 *
 * @access public
 * @param object application $app
 * @returns int Status flags: TK_DBISEARCH_NOT_SEARCHED,
 *              TK_DBISEARCH_NOT_FOUND or TK_DBISEARCH_FOUND.
 */
function tk_dbisearch_has_result (&$app)
{
    if (!isset ($app->tk_dbisearch->query))
        return TK_DBISEARCH_NOT_SEARCHED;

    if (!$app->tk_dbisearch->query->size)
        return TK_DBISEARCH_NOT_FOUND;

    return TK_DBISEARCH_FOUND;
}

/**
 * Get query object of last result.
 *
 * @access public
 * @param object application $app
 * @returns object tk_dbisearch_query.
 */
function tk_dbisearch_get_query_object (&$app)
{
    if (isset ($app->tk_dbisearch->query))
        return $app->tk_dbisearch->query;
}
?>

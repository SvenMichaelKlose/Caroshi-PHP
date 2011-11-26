<?php
# Copyright (c) 2001-2202 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

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

<?php

# Copyright (c) 2001-2202 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

/**
 * List result from tk/dbisearch.
 *
 * @access public
 * @param object application $app
 * @param array $config Configuration for tk_autoform_list_cursor().
 * @see tk_autoform_list_cursor()
 */
function tk_autoform_list_search_results (&$app, $config = '')
{
    $p =& $app->ui;
    $status = tk_dbisearch_has_result ($app);

    # A view is already set up by form_search().
    if ($status == TK_DBISEARCH_FOUND) {
        $cursor =& tk_dbisearch_get_results ($app);
        $p->open_table ();
        tk_autoform_list_cursor ($app, $cursor, $config);
        $p->close_table ();
    }
    return $status;
}

?>

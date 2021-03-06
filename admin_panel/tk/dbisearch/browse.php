<?

/**
 * Browse widget for dbisearch result lists.
 *
 * @access public
 * @module tk_dbisearch_browse
 * @package User interface toolkits
 * @see tk_dbisearch
 */

# Copyright (c) 2002,2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

/**
 * Create button to new result page.
 *
 * @access private
 * @param object application $app
 * @param int $index Index of first record.
 * @param string $label Label for button.
 * @param string $image Path to image for button.
 */
function _tk_dbisearch_browse_button (&$app, $index, $label, $image)
{
    $p =& $app->ui;
    $tv =& $app->event ();

    $q =& tk_dbisearch_get_query_object ($app);
    $q->offset = $index;
    $v =& new event ('tk_dbisearch', array ('query' => $q));
    $v->set_next ($app->event ());
    if ($image)
        $p->image_link ($label, $image, $v);
    else
        $p->link ($label, $v);
}

/**
 * Print widget to browse dbisearch results.
 *
 * This widget occupies a row. tk_dbisearch() must be used before.
 * If there's no more than one page nothing is printed at all.
 *
 * @access public
 * @param object application $app
 * @param string $g_begin Path to image for link to first record.
 * @param string $g_prev Path to image for link to previous record.
 * @param string $g_next Path to image for link to next record.
 * @param string $g_end Path to image for link to last record.
 * @see tk_dbisearch()
 */
function tk_dbisearch_browse (&$app, $g_begin = '', $g_prev = '', $g_next = '', $g_end = '')
{
    $p =& $app->ui;

    $q = tk_dbisearch_get_query_object ($app);
    $l = $q->limit;
    $i = $q->offset;
    $s = $q->size;

    # Don't print anything if there're not enough results.
    if (!$s || $l && $s <= $l)
        return;

    $p->paragraph ();

    $p->open_row (array ('ALIGN' => 'CENTER'));

    # Button to first page.
    if ($i > 0)
        _tk_dbisearch_browse_button ($app, 0, '<<', $g_begin);
    else
        $p->image ('<<', $g_begin);

    # Button to previous page.
    if ($i > 0) {
        $pi = $i >= $l ? $i - $l : 0;
        _tk_dbisearch_browse_button ($app, $pi, '<', $g_prev);
    } else
        $p->image ('<', $g_prev);

    # Print position.
    $tmp = $i + $l > $s ? $s : $i + $l;
    $p->open_cell (array ('ALIGN' => 'CENTER'));
    $p->label (($i + 1) . "-$tmp/$s");
    $p->close_cell ();

    # Button to next page.
    if ($i < $s - $l)
        _tk_dbisearch_browse_button ($app, $i + $l, '>', $g_next);
    else
        $p->image ('>', $g_next);

    # Button to last page.
    if ($i < $s - $l && $s > $l)
        _tk_dbisearch_browse_button ($app, $s - $l, '>>', $g_end);
    else
        $p->image ('>>', $g_end);

    $p->close_row ();

    $p->paragraph ();
}

?>

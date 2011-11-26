<?php
/**
 * Sorting doubly linked lists and trees of them (deprecated).
 *
 * @access public
 * @module dbsort
 * @package Database interfaces
 */

# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function _sort_update ($db, $reftab, $id_parent, $last, $next, $order_clause, $follows)
{
    # Update the last entry.
    $db->update ($reftab, "id_last=$last, id_next=0", "id=$id_parent");
    if ($follows)
        sort_linked_list ($db, $reftab, $id_parent, $order_clause, $follows ? $follows - 1 : $follows);
}

# Sort a doubly-linked list in $table.
#
# $order_clause should contain a sql order clause like 'ORDER BY name ASC'
#   which should work with every table in the defined hierarchy.
#
# $follows contains the number of childs not levels which need sorting as
#   well. -1 sorts the whole tree while 0 does nothing. Use 1 to sort a
#   single list.
#
# All lists that reference $table/$id will be sorted if you don't specify the
# only $child_table to sort.
#
# TODO: php4 will puke on too much recursions, so better unroll this.
function sort_linked_list ($db, $table, $id, $order_clause, $follows = false, $child_table = 0)
{
    $refs =& $db->def->_refs;
    if (!is_array ($refs[$table]))
        return;

    $ref = reset ($refs[$table]);
    if ($child_table)
        while (!$r[$child_table])
            $ref = next ($refs[$table]);

    do {
        $reftab = $ref['table'];
        if ($db->def->is_list ($reftab) == false)
	    return;
        $refid = $ref['id'];

        # Get all records that reference this record in $reftab in the
        # right order.
        if (!($res = $db->select ('id,id_last,id_next', $reftab, "$refid=$id $order_clause")))
            continue; # Continue if there's no sublist.

        # Fetch the first entry.
        $row = $res->get ();
        $former = $row['id'];
        $last = 0;

        # Now fetch the next and update the last.
        while ($row = $res->get ()) {
            # Update references in former entry because right now we know the
	    # primary key of the follower
            _sort_update ($db, $reftab, $former, $last, $row['id'], $order_clause, $follows);
            $db->update ($reftab, "id_last=$last, id_next=" . $row['id'], "id=$former");
	    if ($follows)
                sort_linked_list ($db, $reftab, $former, $order_clause, $follows ? $follows - 1 : $follows);
	    $last = $former;
	    $former = $row['id'];
        }
        _sort_update ($db, $reftab, $former, $last, 0, $order_clause, $follows);
    } while (!$child_table && $ref = next ($refs[$table]));
}
?>

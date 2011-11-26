<?php
/**
 * Get mime type from file(name).
 *
 * @access public
 * @module magic2mime
 * @package File functions
 */

# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


/**
 * Get MIME type of a file.
 *
 * The file name is used to determine the type.
 *
 * @access public
 * @param string $filename Path to file.
 * @returns string MIME type of file.
 */
function magic2mime ($filename)
{
    $grp = 'text';
    $dp = strrpos ($filename, '.');
    $pf = strtolower (substr ($filename, $dp + 1));
    switch ($pf) {
        case 'jpg':
            return 'image/jpeg';
        case 'gif':
        case 'png':
            $grp = 'image';
	    break;
    }
    return "$grp/$pf";
}
?>

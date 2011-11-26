<?php
/**
 * Event handler for MIME-headered output.
 *
 * @access public
 * @module mime
 * @package User interface
 */

# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

/**
 * Initialise this module.
 *
 * @param object application $app
 */
function _mime_init (&$app)
{
    $app->add_function ('__return_mime', TOKEN_REUSE);
}

/**
 * Event handler: Output a file with MIME header.
 *
 * Event argument 'source' contains the source name. 'column' contains the
 * field name, 'primary' contains the primary key name, 'key' contains the
 + key of the record and 'type' contains the MIME type.
 *
 * @param object application $app
 */
function __return_mime (&$app)
{ 
    $table = $app->arg ('source');
    $column = $app->arg ('column');
    $primary = $app->arg ('primary');
    $key = $app->arg ('key');
    $type = strtolower ($app->arg ('type'));
    if ($type == 'image/jpg')
        $type = 'image/jpeg';

    $row = $app->db->select ($column, $table, "$primary=$key")->get ();

    Header ("Content-type: $type");
    echo $row[$column];

    exit;
}
?>

<?php
/**
 * File selector page.
 *
 * @access public
 * @module tk_fsb
 * @package User interface toolkits
 */

# Copyright (c) 2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


/**
 * Initialise the toolkit.
 *
 * @access public
 * @param object application $app
 */
function tk_fsb_init (&$app)
{
    $app->add_function ('tk_fsb');
}

/**
 * Event handler: Toolkit entry point.
 *
 * This function doesn't work.
 *
 * @access public
 * @param object application $app
 */
function tk_fsb (&$app)
{
    $data = $app->arg ('data', ARG_SUB);
    $ret = $app->arg ('ret', ARG_SUB);
    $filefunc = $app->arg ('filefunc', ARG_SUB);
    $dir = $app->arg ('dir', ARG_OPTIONAL);

    $ui =& admin_panel::instance ();

    if (!$dir)
        $dir = $GLOBALS['DOCUMENT_ROOT'];

    echo '[' . $ui->link ('zur&uuml;ck', 'return2caller') . ']';

    echo "<b>Current path: $dir</b><hr>";
    $handle = opendir ($dir);
    $ui->open_table ();
    while (($file = readdir($handle)) !== false) {
        $dirfile = "$dir/$file";
        $ft = filetype ($dirfile);
        $a['dir'] = $dirfile;

        $ui->open_row ();
 
        switch ($ft) {
	    case 'dir':
                $ui->link ($file, new_view ('tk_fsb', $a));
	        break;

	    default:
                $ui->link ($file, $filefunc, array ($ret => $dirfile, 'data' => $data));
        }

        $ui->label ($ft);
        $ui->close_row ();
    }
    $ui->close_table ();
    closedir($handle); 
}
?>

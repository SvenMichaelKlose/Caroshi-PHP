<?php

# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

function error_handler ($errno, $errstr, $file, $line, $context)
{
    echo "<html><body>";
    echo "<b>$errstr</b> in file <b>$file</b> on line <b>$line</b>";
    echo "<pre>";
    debug_print_backtrace ();
    print_r ($context);
    echo "</pre>";
    echo "</body></html>";
    die ();
}

set_error_handler ('error_handler');

?>

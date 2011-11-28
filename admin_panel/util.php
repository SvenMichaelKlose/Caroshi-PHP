<?php
/**
 * Various useful functions.
 *
 * @access public
 * @module util
 * @package User interface
 */

# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


/**
 * Add handler functions to an application.
 *
 * @access public
 * @param object application $app
 * @param array $handlers List of handler names.
 */
function util_add_functions (&$app, &$handlers)
{
    type_array ($handlers);

    foreach ($handlers as $n) {
        $app->add_function ($n);
        $app->raw_views[$n] = true;
    }
}

/**
 * Add 'raw' handler functions to an application.
 *
 * No HTML header is generated for 'raw' handlers.
 *
 * @access public
 * @param object application $app
 * @param array $handlers List of handler names.
 */
function util_add_raw_functions (&$app, &$handlers)
{
    type_array ($handlers);

    foreach ($handlers as $n) {
        $app->add_function ($n);
        $app->raw_views[$n] = true;
    }
}

/**
 * Initialise modules.
 *
 * The init function for each module must start with the module name
 * and end with '_init' (e.g. 'mymodule_init' for module 'mymodule').
 * Each init function takes a reference to the application object.
 *
 * @access public
 * @param object application $app
 * @param array $modules List of module names.
 */
function util_init_modules (&$app, &$modules)
{
    type_array ($modules);
        die ('util_add_modules(): Module list is not an array.');

    foreach ($modules as $m) {
        $m .= '_init';
        $m ($app);
    }
}
?>

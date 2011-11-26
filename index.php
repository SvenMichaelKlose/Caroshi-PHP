<?php
/**
 * General library info.
 *
 * @access public
 * @module libinfo
 * @package Miscellaneous
 */

# Copyright (c) 2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

define ('CAROSHI_VERSION', '0.1.14');
define ('CAROSHI_VERSION_MAJ', '0');
define ('CAROSHI_VERSION_MIN', '1');
define ('CAROSHI_VERSION_MICRO', '14');
define ('CAROSHI_RELEASE_DATE', '2002/08/16 02:18:02 CET');

if (!defined ('PATH_TO_CAROSHI'))
    define ('PATH_TO_CAROSHI', pathinfo (__FILE__, PATHINFO_DIRNAME));
?>

<?php

# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

function dbobj_create_classes (&$db, $classes, $lang)
{
    foreach ($classes as $class) {
        $descr = $lang["class $class"];
        if ($res = $db->select ('*', 'obj_classes', "name='$class'")) {
            $db->update ('obj_classes', "descr='$descr'", 'id=' . $res->get ('id'));
	    continue;
        }
        $db->insert ('obj_classes', "name='$class', descr='$descr'");
    }
}

?>

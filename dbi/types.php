<?php
# XML Schema type name array.
#
# Copyright (c) 2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


function &xml_types ()
{
    return array (
        'string',
        'byte', 'unsignedByte',
        'binary',
        'integer', 'positiveInteger', 'negativeInteger',
        'int', 'unsignedInt',
        'short', 'unsignedShort',
        'decimal', 'float', 'double',
        'boolean',
        'time', 'timeInstant', 'timePeriod', 'timeDuration',
        'date', 'month', 'year', 'century',
        'recurringDay', 'recurringDate', 'recurringDuration',
        'Name', 'QName', 'NCName',
        'uriReference',
        'language'
    );
}

function type_check ($val, $type)
{
    if (!$type)
        return false;
    if (substr ($type, 0, 1) == '!') {
        $type = substr ($type, 1);
        if (!trim ($val))
            return false;
    }
    return true;
}
?>

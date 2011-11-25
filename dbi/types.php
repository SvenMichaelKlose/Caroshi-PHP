<?php
  # $Id: types.php,v 1.6 2002/06/08 19:59:10 sven Exp $
  #
  # XML Schema type name array.
  #
  # Copyright (c) 2001 dev/consulting GmbH
  #                    Sven Michael Klose <sven@devcon.net>
  #
  # This library is free software; you can redistribute it and/or
  # modify it under the terms of the GNU Lesser General Public
  # License as published by the Free Software Foundation; either
  # version 2.1 of the License, or (at your option) any later version.
  #
  # This library is distributed in the hope that it will be useful,
  # but WITHOUT ANY WARRANTY; without even the implied warranty of
  # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
  # Lesser General Public License for more details.
  #
  # You should have received a copy of the GNU Lesser General Public
  # License along with this library; if not, write to the Free Software
  # Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

  function &xml_types ()
  {
    return array (
      'string',
      'byte',
      'unsignedByte',
      'binary',
      'integer',
      'positiveInteger',
      'negativeInteger',
      'int',
      'unsignedInt',
      'short',
      'unsignedShort',
      'decimal',
      'float',
      'double',
      'boolean',
      'time',
      'timeInstant',
      'timePeriod',
      'timeDuration',
      'date',
      'month',
      'year',
      'century',
      'recurringDay',
      'recurringDate',
      'recurringDuration',
      'Name',
      'QName',
      'NCName',
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

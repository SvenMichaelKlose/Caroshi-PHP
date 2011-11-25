<?php
  /**
   * XML template bindings for admin_panel.
   *
   * @access public
   * @module tk_xml_template
   * @package User interface toolkits
   */

  # $Id: xml_template.php,v 1.8 2002/06/01 01:33:18 sven Exp $
  #
  # Copyright (c) 2002 dev/consulting GmbH,
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
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

  # Initialise the module extension.
  # $this: Reference to tk_template obj.
  # $app: Reference to application obj.
  function tk_template_forms_init (&$app, &$this)
  {
    $this->_scanner->dirtag (XML_TEMPLATE_NS, 'FORM-ACTION');
    $this->_app =& $app;
    $app->tk_template =& $this;
  }

  # $this: Reference to application obj.
  function tk_template_get_field (&$this, &$res, $field)
  {
    $ui =& admin_panel::instance ();

    $res["form_$field"] = $ui->new_formfield ($field);
    $res[$field] = $ui->value ($field);
  }

  # $this: Reference to tk_template obj.
  function dirtag_cms_form_action (&$this, $args)
  {
    $ui =& admin_panel::instance ();

    return $ui->url (new event ('form_parser'));
  }
?>

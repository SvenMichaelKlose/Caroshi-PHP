<?php

/**
 * XML template bindings for admin_panel.
 *
 * @access public
 * @module tk_xml_template
 * @package User interface toolkits
 */

# Copyright (c) 2002 dev/consulting GmbH,
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


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
    $ui =& $app->ui;

    $res["form_$field"] = $ui->new_formfield ($field);
    $res[$field] = $ui->value ($field);
}

# $this: Reference to tk_template obj.
function dirtag_cms_form_action (&$this, $args)
{
    $ui =& $app->ui;

    return $ui->url (new event ('form_parser'));
}

?>

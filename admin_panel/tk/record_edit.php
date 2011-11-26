<?php
/**
 * Editor for records without references.
 *
 * This actually doesn't work.
 *
 * @access public
 * @module tk_record_edit
 * @package User interface toolkits
 */

# Copyright (c) 2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

$debug = false;

require_once 'admin_panel/admin_panel.class';
require_once 'admin_panel/tk/auto_form.php';
require_once 'admin_panel/tk/dbisearch.php';
require_once 'dbi/dbi.class';
require_once 'lib/application.class';

# Initialise module.
function tk_record_edit_init (&$this)
{
    $this->add_view ('_tk_record_edit_edit_record', $this);
    $this->add_view ('_tk_record_edit_list_records', $this);

    tk_dbisearch_init ($this);
    tk_autoform_init ($this);
}

# Module entry point - search form.
#
# Arguments:
#  'source':      Source of records to edit.
#  'list_fields': Array of fields that should be displayed in lists.
#                 If not specified all fields are listed.
function tk_record_edit ()
{
    $p =& admin_panel::instance ();

    $id = $this->arg ('id');
    $source = $this->arg ('source', ARG_SUB);
    $list_fields = $this->arg ('list_fields', ARG_SUB | ARG_OPTIONAL);

    if (!$list_fields)
        $fields = array ();

    $p->link ('zur&uuml;ck', 'return2caller');
    $p->link ('&Uuml;bersicht', '_tk_record_edit_list_records');

    tk_autoform_list_search_results (
	$this, 'tk_record_edit', 'id', $source, $list_fields
    );

    $arg = array ('source' => $source, 'mode' => 'all_fields');
    $v =& new event ('form_dbisearch', $arg);
    $v->set_next ($this->event);
    $p->open_source ($source, $v);

    $p->use_filter ('form_safe');
    $p->headline ('Volltextsuche');
    $p->open_row ();
    $p->inputline ('dummy', 60);

    $arg = array ('source' => $source, 'mode' => 'all_fields'),
    $v =& new event ('form_dbisearch', $arg);
    $p->submit_button ('Ok', $v);
    $p->close_row ();
    $p->close_source ();

    $p->open_source ($source);
    $p->use_filter ('form_safe');
    $p->headline ('Detailsuche');
    _tk_record_edit_submit_buttons ($this);
    $p->paragraph ();
    if ($id) {
        $p->get ("WHERE id=$id");

        # Turn off record cache because we need the original data.
        $savedrc = $p->record_cache;
        $p->clear_record_cache ();
    }
    tk_autoform_create_form ($this, $source);
    if ($id)
        $p->record_cache = $savedrc;

    $p->paragraph ();
    _tk_record_edit_submit_buttons ($this);
    $p->close_source ();
}

### HEADSUP: Following functions are _internal.

# Print menu bar for search page.
function _tk_record_edit_submit_buttons ()
{
    $ui =& admin_panel::instance ();
    $id = $this->arg ('id', ARG_OPTIONAL);

    $ui->open_row (array ('ALIGN' => 'CENTER'));

    if (isset ($this->args['id'])) {
        $v =& new event ('_tk_record_edit_edit_record', array ('id' => $id));
        $ui->link ('bearbeiten', $v);
    }

    $v =& new event ('form_check', array ('error_view' => $this->event));
    $arg = array ('ret' => 'id', 'source' => $source);
    $v2 =& new event ('form_create', $arg);
    $v3 =& new event ('tk_record_edit');
    $v2->set_next ($v3);
    $v->set_next ($v2);
    $ui->submit_button ('Neu erstellen', $v);

    $ui->link ('Reset', 'tk_record_edit');

    $v =& new event ('form_dbisearch', array ('ret' => 'id'));
    $v->set_next ('tk_record_edit');
    $ui->submit_button ('Suche', $v);
    $ui->close_row ();
}

# Print menu bar for record editor.
function _tk_record_edit_update_buttons ()
{
    $p =& admin_panel::instance ();

    $p->open_row (array ('ALIGN' => 'CENTER'));

    $v =& new event ('record_delete');
    $v->set_next ($this->event);
    $p->link ('entfernen', $v);

    $p->reset_button ('Undo');

    $v =& new event ('form_check', array ('error_view' => $this->event));
    $v2 =& new event ('form_update');
    $v2->set_next ($this->event);
    $v->set_next ($v2);
    $p->submit_button ('Sichern', $v);

    $p->close_row ();
}

# Bearbeitungsseite fuer Kundenentrag.
function _tk_record_edit_edit_record (&$this)
{
    $p =& admin_panel::instance ();

    $id = $this->arg ('id');
    $source = $this->arg ('source', ARG_SUB);

    if (isset ($p->record_cache[$source]['_last']))
        unset ($p->record_cache[$source]['_last']);
    $p->headline ('Eintrag bearbeiten');
    $p->link ('zur&uuml;ck', 'tk_record_edit');
    $p->open_source ($source);
    $p->use_filter ('form_safe');
    $p->get ("WHERE id=$id");
    _tk_record_edit_update_buttons ($this);
    $p->paragraph ();
    tk_autoform_create_form ($this, $source);
    $p->paragraph ();
    _tk_record_edit_update_buttons ($this);
    $p->close_source ();
}

# Uebersichtsseite aller Kunden.
function _tk_record_edit_list_records (&$this)
{
    $p =& admin_panel::instance ();

    $source = $this->arg ('source', ARG_SUB);
    $list_fields = $this->arg ('list_fields', ARG_SUB);
    $sortkey = $this->arg ('sortkey', ARG_OPTIONAL);

    $p->clear_record_cache ();

    if (!$sortkey)
	$sortkey = 'firma';

    $p->headline ('&Uuml;bersicht aller Eintr&auml;ge');
    $p->link ('zur&uuml;ck', 'tk_record_edit');

    $v = $this->event;
    if ($sortkey == 'firma') {
        $v->set_args (array ('sortkey' => 'id'));
	$p->link ('Sortierung nach Kundennummer', $v);
    } else {
        $v->set_args (array ('sortkey' => 'firma'));
	$p->link ('Sortierung nach Firma', $v);
    }
    $selection = "ORDER BY $sortkey ASC";
    $p->open_source ($source); # XXX
    tk_autoform_list_results ($this, 'tk_record_edit', 'id', $source,
                              $list_fields, $selection);
    $p->close_source ();
}
?>

<?php
/**
 * dbconf.class editor for databased application configuration.
 *
 * @access public
 * @module tk_dbconf
 * @package User interface toolkits
 */

# Copyright (c) 2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

include 'cursor/dbconf.class.php';
include 'admin_panel/tk/fsb.php';

########################
### Public functions ###
########################

/**
 * Call this before init'ing admin_panel.
 *
 * @access public
 * @param object application $this
 * @param object dbconf $dbconf Should pass this to tk_dbconf by event arg.
 */
function tk_dbconf_init (&$this, &$dbconf)
{
    $ui =& admin_panel::instance ();

    cursor_dbconf::set_dbconf ($dbconf);
    $dbconf =& new cursor_dbconf ($dbconf);

    $this->add_function ('tk_dbconf');

    util_add_raw_functions ($this, array ('_tk_dbconf_update', '_tk_dbconf_reset', '_tk_dbconf_reset_ask', '_tk_dbconf_set_file'));

    tk_fsb_init ($this);
}

/**
 * Event handler: Toolkit entry point.
 *
 * @access public
 * @param object application $this
 */
function tk_dbconf (&$this)
{
    $p =& admin_panel::instance ();

    $p->headline ('Konfiguration');

    $p->link ('Startseite', 'return2caller');

    $v =& new event ('_tk_dbconf_update');
    $v->set_next ($this->event);
    $p->link ('Konfiguration updaten', $v);

    $v =& new event ('_tk_dbconf_reset_ask');
    $v->set_next ($this->event);
    $p->link ('Konfiguration auf Werkseinstellung zur&uuml;cksetzen', $v);

    # Konfigurierbare Gruppen.
    __tk_dbconf_fields ($this, 'cnf', 'Allgemeine Einstellungen');
    __tk_dbconf_fields ($this, 'tpl', 'Layoutvorlagen');
    __tk_dbconf_fields ($this, 'msg', 'Meldungen');
}

function __tk_dbconf_fields (&$this, $sel, $headline)
{
    $p =& admin_panel::instance ();
    $db =& $this->db;

    # Try to fetch an item and return if there's none.
    $cursor =& new cursor_dbconf ($db);
    $cursor->set_source ($GLOBALS['config_table']);
    $p->open_context ($cursor);
    # $p->set_cursor ('dbconf', $null);
    $tmp = $cursor->get ($sel);
    $p->close_context ();
    if (!$tmp)
        return;

    $null = 0;
    $cursor =& new cursor_dbconf ($db);
    $cursor->set_source ($GLOBALS['config_table']);

    $p->open_context ($cursor);
    $p->headline ($headline);
    $p->open_table ();

    $form_event =& new event ('form_update');
    $form_event->set_next (new event ('tk_dbconf'));
    $p->open_form ($form_event);

    if ($cursor->query ($sel)) {
        while ($rec =& $cursor->get ()) {
	    $p->open_row ();

	    $p->show ('descr');

            $arg = array ('filefunc' => '_tk_dbconf_set_file',
                          'data' => $rec['name'], 'ret' => 'file');
            $v =& new event ('tk_fsb', $arg);
            $v->set_caller ($this->event);
            $p->link ('Auswahl', $v);

	    $p->radiobox ('is_file', 'yes', 'no');

 	    $tmp = $rec['mime'];
	    $grp = strtolower (substr ($tmp, strpos ($tmp, '/')));
	    $p->inputline ('data', 60);

	    $p->close_row ();
        }
        $p->paragraph ();

       $p->submit_button ('Ok', $form_event);
    } else
        $p->label ('Keine Konfigurationseintr&auml;ge');
    $p->close_form ();
    $p->close_table ();
    $p->close_context ();
}

function _tk_dbconf_set_file (&$this)
{
    $data = $this->arg ('data');
    $file = $this->arg ('file');

    $this->conf->set ($data, $file, true);

    $this->call ('return2caller');
}

function _tk_dbconf_reset_ask (&$this)
{
    $ui =& admin_panel::instance ();

    $ui->confirm ('Wirklich die Konfiguration auf Werkseinstellungen zur&uuml;cksetzen?',
                  'Ja, alte Einstellungen verwerfen.', '_tk_dbconf_reset',
                  'Nein, abbrechen.', 'return2caller');
    $this->call ('return2caller');
}

function _tk_dbconf_update (&$this)
{
    global $lang, $conf;

    $ui =& admin_panel::instance ();

    # Define config items using the language definition.
    $num = 0;
    foreach ($lang as $key => $value) {
        if (substr ($key, 0, 4) != 'cnf ')
	    continue;
        $key = substr ($key, 4);
        if ($this->conf->exists ($key))
	    continue;
        $num++;
        $this->conf->create ($key, $value);
        $is_file = 0;
        if (substr ($value = $conf['de'][$key], 0, 1) == '@') {
	    $value = substr ($value, 1);
	    $is_file = 1;
        }
        $this->conf->set ($key, $value, $is_file);
    }
    $ui->msgbox ("Konfiguration wurde erneuert. $num neue Eintraege.");
    $this->call ('return2caller');
}

function _tk_dbconf_reset (&$this)
{
    global $lang, $conf;

    $ui =& admin_panel::instance ();

    if (!is_array ($conf)) {
        $ui->msgbox ('Keine Werkskonfiguration vorhanden.', 'yellow');
        $this->call ('return2caller');
        return;
    }

    # Define config items using the language definition.
    foreach ($lang as $key => $value) {
        if (substr ($key, 0, 4) != 'cnf ')
	    continue;
        $key = substr ($key, 4);
        $this->conf->create ($key, $value);
        $is_file = 0;
        if (isset ($conf['de'][$key])
	    && substr ($value = $conf['de'][$key], 0, 1) == '@') {
            $value = substr ($value, 1);
	    $is_file = 1;
        }
        $this->conf->set ($key, $value, $is_file);
    }
    $ui->msgbox ('Konfiguration wurde zur&uuml;ckgesetzt.');
    $this->call ('return2caller');
}
?>

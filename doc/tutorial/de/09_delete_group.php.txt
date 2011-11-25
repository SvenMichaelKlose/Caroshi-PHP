<?php
include 'proc/application.class';
include 'admin_panel/admin_panel.class';
include 'admin_panel/tk/range_edit/range_edit.php';

class list_records extends application {

  function init ()
  {
    $db =& $this->db;
    $def =& $db->def;

    # Definiere Tabelle 'personen' mit Feldnamen und SQL-Typ.
    $def->define_table (
      'personen',
      array (array ('n' => 'id',
                    't' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY'),
             array ('n' => 'name',
	            't' => 'VARCHAR(255) NOT NULL'),
             array ('n' => 'vorname',
	            't' => 'VARCHAR(255) NOT NULL'),
             array ('n' => 'email',
	            't' => 'VARCHAR(255) NOT NULL'),
             array ('n' => 'fon',
	            't' => 'VARCHAR(255) NOT NULL'),
             array ('n' => 'fax',
	            't' => 'VARCHAR(255) NOT NULL'))
    );
    # Name des Primaerschluessels angeben.
    $def->set_primary ('personen', 'id');

    $this->add_method ('person_editieren', $this);
    $this->add_method ('person_erstellen', $this);
    tk_range_edit_init ($this);

    $ui =& new admin_panel ($this);
    admin_panel::instance ($ui);
    $ui->header ('Personendatenbank');
  }

  function close ()
  {
    $p =& admin_panel::instance ();
    $p->close ();
  }

  function defaultview ()
  {
    $ui =& admin_panel::instance ();

    # Ueberschrift ausgeben.
    $ui->headline ('&Uuml;bersicht der Personen');

    # Link zum erstellen eines neuen Eintrags ausgeben.
    $ui->link ('Neuen Eintrag erstellen', 'person_erstellen');

    $ui->paragraph ();

    # SQL-Tabelle 'personen' oeffnen.
    $ui->open_source ('personen');

    # Saemltiche Eintraege waehlen.
    if ($ui->query ()) {
      # Forminhalt merken, falls submit-Button betaetigt wird.
      $ui->use_filter ('form_safe');

      # Saemtliche Eintraege ausgeben.
      while ($r =& $ui->get ()) {
	# Alle Felder in einzelnerZeile darstellen.
        $ui->open_row ();

        # Checkbox fuer Gruppen-Auswahl.
        $ui->checkbox ('marker');

        # Argument fuer Link zur Bearbeitungsseite des Eintrags.
        $arg = array ('id' => $r['id']);
        $v =& new event ('person_editieren', $arg);
        foreach (array ('name', 'vorname', 'email', 'fon', 'fax') as $feld)
          $ui->link ($r[$feld] ? $r[$feld] : '-', $v);

        $ui->close_row ();
      }

      $ui->paragraph ();
      $ui->open_row ();

      # Funktion zur Auswahl von Eintraegen zwischen zwei neu gewaehlten.
      $arg = array ('marker_field' => 'marker');
      $v =& new event ('tk_range_edit_select', $arg);
      $v->set_next ($this->event);
      $ui->submit_button ('Bereich waehlen', $v);

      # Je nach Status Funktionen zur aus- oder abwahl aller Eintraege
      # anbieten.
      $sel = tk_range_edit_all_selected ($this, 'marker');
      if ($sel == 0 || $sel == 2) {
        $v =& new event ('tk_range_edit_select_all', $arg);
        $v->set_next ($this->event);
        $ui->submit_button ('alles waehlen', $v);
      }
      if ($sel == 1 || $sel == 2) {
        $arg = array ('marker_field' => 'marker');
        $v =& new event ('tk_range_edit_unselect_all', $arg);
        $v->set_next ($this->event);
        $ui->submit_button ('alles abwaehlen', $v);
      }

      # Funktion zum loeschen gewaehlter Eintraege ausgeben.
      $arg = array ('table' => 'personen', '_ssi_obj' => 'dbi');
      $v =& new event ('record_delete', $arg);
      $v->set_next ($this->event ());
      $arg = array ('view' => $v, 'argname' => 'cursor_list',
                    'marker_field' => 'marker');
      $v =& new event ('tk_range_edit_call', $arg);
      $ui->submit_button ('loeschen', $v);

      $ui->close_row ();
    } else
      # Hinweis ausgeben, dass keine Eintraege vorhanden sind.
      $ui->print_text ('Keine Eintr&auml;ge vorhanden.');

    $ui->close_source ();
  }

  function person_formular ()
  {
    $ui =& admin_panel::instance ();

    $felder = array ('Name' => 'name', 'Vorname' => 'vorname',
                     'Email' => 'email', 'Fon' => 'fon', 'Fax' => 'fax');
    $ui->open_table ();
    foreach ($felder as $beschreibung => $feldname) {
      $ui->open_row ();
      $ui->label ($beschreibung);
      $ui->inputline ($feldname, 40);
      $ui->close_row ();
    }
    $ui->close_table ();
  }

  function person_editieren ()
  {
    $id = $this->arg ('id');

    $ui =& admin_panel::instance ();

    # Ueberschrift ausgeben.
    $ui->headline ('Person bearbeiten');
    $ui->link ('zur&uuml;ck', 'defaultview');

    # SQL-Tabelle 'personen' auswaehlen.
    $ui->open_source ('personen');

    # Datensatz mit angegebenem Primaerschluessel auswaehlen.
    $ui->get ("id=$id");

    # Formular mit Datensatz als Inhalt ausgeben.
    $this->person_formular ();

    $ui->paragraph ();

    $ui->open_row ();

    # Nach Aufruf von record_delete defaultview darstellen.
    $arg = array ('no_view' => $this->event);
    $v =& new event ('record_delete', $arg);
    $v->set_next (new event ('defaultview'));
    $ui->link ('Eintrag entfernen', $v);

    # Submitbutton zum aendern des Datensatzes in der Datenbank beim posten
    # des Formulars erzeugen.
    $v =& new event ('form_update');
    $v->set_next ($this->event);
    $ui->submit_button ('Ok', $v);

    $ui->close_row ();

    # Kontext schliessen.
    $ui->close_source ();
  }


  function person_erstellen ()
  {
    $ui =& admin_panel::instance ();

    $ui->headline ('Person erstellen');

    $ui->open_source ('personen');

    $this->person_formular ();

    $ui->paragraph ();

    $arg = array ('next_view' => 'person_editieren', 'ret' => 'id');
    $v =& new event ('form_create', $arg);
    $ui->submit_button ('Eintrag erstellen', $v);

    $ui->close_source ();
  }
}

$app =& new list_records ();
$app->run ();
?>

<?php
include 'proc/application.class';
include 'admin_panel/admin_panel.class';

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

    # Funktion 'person_editieren' als View definieren.
    $this->add_method ('person_editieren', $this);

    $p =& new admin_panel ($this);
    $p->header ('Personendatenbank');
    admin_panel::instance ($p);
  }

  function close ()
  {
    $p =& admin_panel::instance ();
    $p->close ();
  }

  function defaultview ()
  {
    $ui =& admin_panel::instance ();

    $ui->headline ('&Uuml;bersicht der Personen');
    $ui->open_table ();
    $ui->open_source ('personen');
    if ($ui->query ('')) {
      $index = 1;
      while ($row =& $ui->get ()) {
        $arg = array ('id' => $row['id']);
        $v = new event ('person_editieren', $arg);
        $ui->open_row ();
        $ui->print_text ("$index.");
        $index++;
        foreach (array ('name', 'vorname', 'email', 'fon', 'fax') as $feldname)
          $ui->link ($row[$feldname], $v);
        $ui->close_row ();
      }
    } else
      $ui->print_text ('Keine Eintr&auml;ge vorhanden.');
    $ui->close_table ();
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

    # SQL-Tabelle 'personen' auswaehlen.
    $ui->open_source ('personen');

    # Datensatz mit angegebenem Primaerschluessel auswaehlen.
    $ui->get ("id=$id");

    # Formular mit Datensatz als Inhalt ausgeben.
    $this->person_formular ();

    $ui->paragraph ();

    # Submitbutton zum aendern des Datensatzes in der Datenbank beim posten
    # des Formulars erzeugen.
    $v =& new event ('form_update');
    $v->set_next ($this->v);
    $ui->submit_button ('Ok', $v);

    # Kontext schliessen.
    $ui->close_source ();
  }
}

$app =& new list_records ();
$app->run ();
?>

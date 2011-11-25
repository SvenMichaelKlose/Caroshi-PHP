<?php
include 'proc/application.class';
include 'admin_panel/admin_panel.class';

class form_in_table extends application {

  function init ()
  {
    $p =& new admin_panel ($this);
    $p->header ('Caroshi example: Form in a table.');
    admin_panel::instance ($p);
  }

  function close ()
  {
    $p =& admin_panel::instance ();
    $p->close ();
  }

  function defaultview ()
  {
    $this->person_formular ();
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
}

$app =& new form_in_table ();
$app->run ();
?>

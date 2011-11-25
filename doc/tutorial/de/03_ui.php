<?php
include 'proc/application.class';
include 'admin_panel/admin_panel.class';

class hello_world_ui extends application {

  function init ()
  {
    $p =& new admin_panel ($this);
    admin_panel::instance ($p);
    $p->header ('Example application');
  }

  function close ()
  {
    $p =& admin_panel::instance ();
    $p->close ();
  }

  function defaultview ()
  {
    $p =& admin_panel::instance ();

    $p->print_text ('Hello world!');
  }
}

$app =& new hello_world_ui ();
$app->run ();
?>

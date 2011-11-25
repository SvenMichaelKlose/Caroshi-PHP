<?php
include 'proc/application.class';

class hello_world_linked extends application {

  function init ()
  {
    $this->add_method ('zeige_text', $this);
  }

  function close ()
  {
  }


  function defaultview ()
  {
    $arg = array ('text' => 'Hello world!');
    $v =& new event ('zeige_text', $arg);
    echo '<a href="' . $this->link ($v) . '">Text zeigen</a>';
  }

  function zeige_text ()
  {
    echo $this->arg ('text');
  }
}

$app =& new hello_world_linked ();
$app->run ();
?>

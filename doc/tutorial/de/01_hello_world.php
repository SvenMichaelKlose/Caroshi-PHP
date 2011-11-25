<?php
include 'proc/application.class';

class hello_world extends application {

  function init ()
  {
  }

  function close ()
  {
  }

  function defaultview ()
  {
    echo 'Hello world!';
  }
}

$app =& new hello_world ();
$app->run ();
?>

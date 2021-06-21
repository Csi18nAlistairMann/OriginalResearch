<?php

/*
  cli

  Routines useful for dealing with the command line. Dialog(1) makes heavy use
  of these.
*/

class cli
{
  public static function escape($val) {
    $val = str_replace("'", "'\''", $val);
    return $val;
  }
}

?>

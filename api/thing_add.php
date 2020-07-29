<?php

/*
  thing_add

  Entry point for CLI calls to add a simple Thing
 */

require_once('classes/effort02_class.php');
require_once('classes/things_class.php');

$effort = new effort02;

// What we're searching for and what we'll link it to
$rv = 1;
if ($argc !== 9) {
  $effort->err(__FILE__, "expected seven arguments");

} else {
  $projname = $argv[1];
  $type = $argv[2];
  $tag = $argv[3];
  $ts = $argv[4];
  $user = $argv[5];
  $text = $argv[6];
  $nuance = $argv[7];
  $dupes = $argv[8];

  $observing_duplicates = false;
  if (strtolower($dupes) === 'dupes-ok') {
    $observing_duplicates = true;
  }

  $things = new things($projname);
  $things->load();
  $thing = new thing;
  $thing->nuance($nuance);
  $thing->type($type);
  $thing->tag($tag);
  $thing->timestamp($ts);
  $thing->user($user);
  $thing->text($text);

  $saveF = false;
  if ($observing_duplicates === true &&
      $things->addIfNotDuplicate($thing) === true) {
    $saveF = true;
  } else {
    if ($things->add($thing) === true) {
      $saveF = true;
    }
  }
  if ($saveF === true) {
    return($things->save());
  }
  return(false);
}

return $rv;
?>

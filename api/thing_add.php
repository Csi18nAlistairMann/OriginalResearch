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
if ($argc !== 7) {
  $effort->err(__FILE__, "expected six arguments");

} else {
  $projname = $argv[1];
  $type = $argv[2];
  $tag = $argv[3];
  $ts = $argv[4];
  $user = $argv[5];
  $text = $argv[6];

  $things = new things($projname);
  $things->load();
  $thing = new thing;
  $thing->type($type);
  $thing->tag($tag);
  $thing->timestamp($ts);
  $thing->user($user);
  $thing->text($text);
  if ($things->addIfNotDuplicate($thing) === true) {
    // NOT satisfactory right now as two people with the
    // same name uploaded by the same person would count
    // as a duplicate.
    return($things->save());
  } else {
    return false;
  }
}

return $rv;

?>

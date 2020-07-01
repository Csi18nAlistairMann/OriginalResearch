<?php

/*
  aka_add

  Entry point for CLI requests to add a simple aka
 */

require_once('classes/effort02_class.php');
require_once('classes/akas_class.php');

$effort = new effort02;

// What we're searching for and what we'll aka it to
$rv = 1;
if ($argc !== 4) {
  $effort->err(__FILE__, "expected four arguments");

} else {
  $projname = $argv[1];
  $from = $argv[2];
  $tag = $argv[3];

  $akas = new akas($projname);
  $akas->load();

  $found = false;
  foreach($akas->db as $aka) {
    if (($aka->from() === $from && $aka->to() === $tag)
	||
	($aka->to() === $from && $aka->from() === $tag)) {
      $found = true;
    }
  }
  if ($found === false) {
    $aka = new aka;
    $aka->from($from);
    $aka->to($tag);
    $akas->db[] = $aka;
    $rv =  $akas->save();
  }
}

return $rv;

?>

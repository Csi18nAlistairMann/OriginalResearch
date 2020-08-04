<?php

/*
  display-or-words

  Display all the available things->text() with a divider between
  each.

  This is used elsewhere to exclude items already being tracked by
  the OR database.
*/

//
// The divider is used to assist handling newlines in the Thing name. It should
// be unique, and is also the first entry in the output so other scripts can
// identify it.
define("DIVIDER", "---abc123---\n");

require_once('classes/things_class.php');
require_once('classes/effort02_class.php');

// https://stackoverflow.com/questions/838227/php-sort-an-array-by-the-length-of-its-values
function sortByLength($a, $b){
  return strlen($b) - strlen($a);
}

$effort = new effort02;

// What tag will we show? 0 for 'first available'
if ($argc !== 2) {
  $effort->err(__FILE__, "Bad args? [project name]");
  exit;

} else {
  $projname = $argv[1];
}

// Get the entries: we want the text in lower case
$things = new things($projname);
$things->load();
$p = array();
foreach($things->db as $thing) {
  if (substr($thing->text(), 0, strlen('https://')) === 'https://'
      ||
      substr($thing->text(), 0, strlen('http://')) === 'http://') {
    $p[] = $thing->text();
  } else {
    $p[] = strtolower($thing->text());
  }
}

// now order the entries by length: otherwise shorter
// entries won't be processed before longer entries
// that contain that shorter entry.
usort($p, 'sortByLength');

print_r(DIVIDER);
foreach($p as $text) {
  print_r($text . "\n");
  print_r(DIVIDER);
}

?>

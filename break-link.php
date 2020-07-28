<?php

/*
  break-link

  The user has asked us to remove a link from the
  links database.
*/

require_once('defines.php');
require_once('classes/links_class.php');
require_once('classes/effort02_class.php');

$effort = new effort02;

// What we're searchig for and what we'll link it to
if ($argc !== 4) {
  $effort->err(__FILE__ . ": expected three arguments");
  exit;

} else {
  $projname = $argv[1];
  $arg_from = $argv[2];
  $arg_to = $argv[3];
}

$links = new links($projname);
$links->load();
$connections = array();
foreach($links->db as $item) {
  if (!(($item->subject() === $arg_from && $item->object() === $arg_to)
	||
	($item->object() === $arg_from && $item->subject() === $arg_to))) {
    $connections[] = $item;
  }
}

$links->db = $connections;
$links->save();

$effort->whatToShowNext($arg_from);
?>
